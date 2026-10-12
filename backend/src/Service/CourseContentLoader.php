<?php

namespace App\Service;

use App\DTO\Chapter;
use App\DTO\LessonContent;
use App\DTO\LessonSummary;
use App\DTO\QuizQuestion;
use App\DTO\Track;
use App\Exception\LessonNotFoundException;
use App\Exception\MalformedLessonException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads every lesson Markdown file under backend/courses/, turns it into a structured
 * LessonContent DTO, and builds the course navigation tree (tracks -> chapters -> lessons).
 *
 * Markdown files are the single source of truth for lesson content: nothing here is
 * duplicated into the database. The result is cached in memory for the lifetime of the
 * request/process because scanning the filesystem on every request would be wasteful and
 * course content only changes when a developer edits a file and clears the cache.
 */
class CourseContentLoader
{
    private const REQUIRED_SECTIONS = [
        'Learning Objective' => 'learningObjective',
        'Explanation' => 'explanation',
        'Why It Matters' => 'whyItMatters',
        'Example' => 'example',
        'Code' => 'code',
        'Code Explanation' => 'codeExplanation',
        'Common Mistakes' => 'commonMistakes',
        'Best Practices' => 'bestPractices',
        'Exercise' => 'exercise',
        'Solution' => 'solution',
        'Mini Quiz' => 'miniQuiz',
    ];

    /** @var array<string, LessonContent>|null */
    private ?array $lessonsBySlug = null;

    /** @var array<string, LessonSummary>|null keyed by slug, in global course order */
    private ?array $summariesInOrder = null;

    /** @var Track[]|null */
    private ?array $tracks = null;

    /** @var array<string, string> path => reason, populated while scanning */
    private array $malformedFiles = [];

    public function __construct(private readonly string $coursesDir)
    {
    }

    /**
     * @return Track[]
     */
    public function getCourseTree(): array
    {
        $this->ensureLoaded();

        return $this->tracks;
    }

    public function getLesson(string $slug): LessonContent
    {
        $this->ensureLoaded();

        if (!isset($this->lessonsBySlug[$slug])) {
            throw new LessonNotFoundException($slug);
        }

        return $this->lessonsBySlug[$slug];
    }

    /**
     * @return string[] absolute paths of files that failed to parse (for logging/tests)
     */
    public function getMalformedFiles(): array
    {
        $this->ensureLoaded();

        return $this->malformedFiles;
    }

    /**
     * @return array<int, array{lesson: LessonSummary, excerpt: string}>
     */
    public function search(string $query): array
    {
        $this->ensureLoaded();

        $needle = mb_strtolower(trim($query));
        if ('' === $needle) {
            return [];
        }

        $results = [];
        foreach ($this->lessonsBySlug as $lesson) {
            $haystacks = [
                mb_strtolower($lesson->title),
                mb_strtolower($lesson->chapterTitle),
                mb_strtolower(implode(' ', $lesson->tags)),
                mb_strtolower(implode(' ', $lesson->sections)),
            ];

            $matchedText = null;
            foreach ($haystacks as $haystack) {
                if (str_contains($haystack, $needle)) {
                    $matchedText = $haystack;
                    break;
                }
            }

            if (null === $matchedText) {
                continue;
            }

            $results[] = [
                'lesson' => $this->summariesInOrder[$lesson->slug],
                'excerpt' => $this->buildExcerpt($matchedText, $needle),
            ];
        }

        return $results;
    }

    private function buildExcerpt(string $haystack, string $needle): string
    {
        $position = mb_strpos($haystack, $needle);
        $start = max(0, $position - 40);
        $excerpt = mb_substr($haystack, $start, 140);

        return trim(($start > 0 ? '…' : '').$excerpt.'…');
    }

    private function ensureLoaded(): void
    {
        if (null !== $this->lessonsBySlug) {
            return;
        }

        $this->lessonsBySlug = [];
        $this->malformedFiles = [];
        $rawLessons = [];

        if (!is_dir($this->coursesDir)) {
            $this->summariesInOrder = [];
            $this->tracks = [];

            return;
        }

        $finder = new Finder();
        $finder->files()->in($this->coursesDir)->name('*.md')->sortByName();

        foreach ($finder as $file) {
            try {
                $rawLessons[] = $this->parseLessonFile($file->getRealPath());
            } catch (MalformedLessonException $exception) {
                $this->malformedFiles[$file->getRealPath()] = $exception->getMessage();
            }
        }

        usort($rawLessons, function (array $a, array $b) {
            return [$a['trackOrder'], $a['chapterOrder'], $a['order']]
                <=> [$b['trackOrder'], $b['chapterOrder'], $b['order']];
        });

        $summaries = [];
        $globalOrder = 0;
        foreach ($rawLessons as $raw) {
            ++$globalOrder;
            $summaries[$raw['slug']] = new LessonSummary(
                slug: $raw['slug'],
                title: $raw['title'],
                track: $raw['track'],
                chapter: $raw['chapter'],
                chapterTitle: $raw['chapterTitle'],
                difficulty: $raw['difficulty'],
                order: $raw['order'],
                globalOrder: $globalOrder,
            );
        }

        $orderedSlugs = array_keys($summaries);

        foreach ($rawLessons as $index => $raw) {
            $slug = $raw['slug'];
            $previousSlug = $orderedSlugs[$index - 1] ?? null;
            $nextSlug = $orderedSlugs[$index + 1] ?? null;

            $this->lessonsBySlug[$slug] = new LessonContent(
                slug: $slug,
                track: $raw['track'],
                trackTitle: $raw['trackTitle'],
                chapter: $raw['chapter'],
                chapterTitle: $raw['chapterTitle'],
                title: $raw['title'],
                difficulty: $raw['difficulty'],
                order: $raw['order'],
                estimatedMinutes: $raw['estimatedMinutes'],
                tags: $raw['tags'],
                sections: $raw['sections'],
                quiz: $raw['quiz'],
                previous: $previousSlug ? $summaries[$previousSlug] : null,
                next: $nextSlug ? $summaries[$nextSlug] : null,
            );
        }

        $this->summariesInOrder = $summaries;
        $this->tracks = $this->buildTree($rawLessons, $summaries);
    }

    /**
     * @param array<int, array<string, mixed>>  $rawLessons
     * @param array<string, LessonSummary>      $summaries
     *
     * @return Track[]
     */
    private function buildTree(array $rawLessons, array $summaries): array
    {
        $tracksByKey = [];

        foreach ($rawLessons as $raw) {
            $trackKey = $raw['track'];
            $chapterKey = $raw['chapter'];

            $tracksByKey[$trackKey] ??= [
                'title' => $raw['trackTitle'],
                'order' => $raw['trackOrder'],
                'chapters' => [],
            ];

            $tracksByKey[$trackKey]['chapters'][$chapterKey] ??= [
                'title' => $raw['chapterTitle'],
                'order' => $raw['chapterOrder'],
                'lessons' => [],
            ];

            $tracksByKey[$trackKey]['chapters'][$chapterKey]['lessons'][] = $summaries[$raw['slug']];
        }

        uasort($tracksByKey, fn ($a, $b) => $a['order'] <=> $b['order']);

        $tracks = [];
        foreach ($tracksByKey as $trackKey => $trackData) {
            uasort($trackData['chapters'], fn ($a, $b) => $a['order'] <=> $b['order']);

            $chapters = [];
            foreach ($trackData['chapters'] as $chapterKey => $chapterData) {
                $chapters[] = new Chapter(
                    key: $chapterKey,
                    title: $chapterData['title'],
                    order: $chapterData['order'],
                    lessons: $chapterData['lessons'],
                );
            }

            $tracks[] = new Track(
                key: $trackKey,
                title: $trackData['title'],
                order: $trackData['order'],
                chapters: $chapters,
            );
        }

        return $tracks;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLessonFile(string $path): array
    {
        $raw = file_get_contents($path);
        if (false === $raw) {
            throw new MalformedLessonException($path, 'File could not be read.');
        }

        if (!preg_match('/^---\r?\n(.*?)\r?\n---\r?\n(.*)$/s', $raw, $matches)) {
            throw new MalformedLessonException($path, 'Missing YAML frontmatter block ("--- ... ---").');
        }

        [, $frontmatterRaw, $body] = $matches;

        try {
            $frontmatter = Yaml::parse($frontmatterRaw) ?? [];
        } catch (\Throwable $exception) {
            throw new MalformedLessonException($path, 'Invalid YAML frontmatter: '.$exception->getMessage());
        }

        $required = ['slug', 'track', 'trackTitle', 'trackOrder', 'chapter', 'chapterTitle', 'chapterOrder', 'title', 'difficulty', 'order'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $frontmatter)) {
                throw new MalformedLessonException($path, sprintf('Missing frontmatter field "%s".', $field));
            }
        }

        $sections = $this->parseSections($path, $body);
        $quiz = $this->parseQuiz($path, $sections['miniQuiz']);
        unset($sections['miniQuiz']);

        return [
            'slug' => (string) $frontmatter['slug'],
            'track' => (string) $frontmatter['track'],
            'trackTitle' => (string) $frontmatter['trackTitle'],
            'trackOrder' => (int) $frontmatter['trackOrder'],
            'chapter' => (string) $frontmatter['chapter'],
            'chapterTitle' => (string) $frontmatter['chapterTitle'],
            'chapterOrder' => (int) $frontmatter['chapterOrder'],
            'title' => (string) $frontmatter['title'],
            'difficulty' => (string) $frontmatter['difficulty'],
            'order' => (int) $frontmatter['order'],
            'estimatedMinutes' => (int) ($frontmatter['estimatedMinutes'] ?? 10),
            'tags' => array_map('strval', $frontmatter['tags'] ?? []),
            'sections' => $sections,
            'quiz' => $quiz,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseSections(string $path, string $body): array
    {
        $pattern = '/^##\s+(.+?)\s*$/m';
        preg_match_all($pattern, $body, $headingMatches, PREG_OFFSET_CAPTURE);

        if ([] === $headingMatches[0]) {
            throw new MalformedLessonException($path, 'No "## Section" headings found in lesson body.');
        }

        $sections = [];
        $count = count($headingMatches[0]);
        for ($i = 0; $i < $count; ++$i) {
            $headingText = trim($headingMatches[1][$i][0]);
            $contentStart = $headingMatches[0][$i][1] + strlen($headingMatches[0][$i][0]);
            $contentEnd = $i + 1 < $count ? $headingMatches[0][$i + 1][1] : strlen($body);
            $content = trim(substr($body, $contentStart, $contentEnd - $contentStart));

            $key = self::REQUIRED_SECTIONS[$headingText] ?? null;
            if (null === $key) {
                // Unknown heading: ignore rather than fail, in case authors add supplementary notes.
                continue;
            }

            $sections[$key] = $content;
        }

        $missing = array_diff(array_values(self::REQUIRED_SECTIONS), array_keys($sections));
        if ([] !== $missing) {
            throw new MalformedLessonException($path, 'Missing required section(s): '.implode(', ', $missing));
        }

        // The "Code" section is expected to be a single fenced code block; extract it cleanly.
        if (preg_match('/^```(\w*)\r?\n(.*?)\r?\n```$/s', $sections['code'], $codeMatch)) {
            $sections['codeLanguage'] = '' !== $codeMatch[1] ? $codeMatch[1] : 'text';
            $sections['code'] = $codeMatch[2];
        } else {
            $sections['codeLanguage'] = 'text';
        }

        return $sections;
    }

    /**
     * @return QuizQuestion[]
     */
    private function parseQuiz(string $path, string $miniQuizSection): array
    {
        if (!preg_match('/^```ya?ml\r?\n(.*?)\r?\n```$/s', $miniQuizSection, $match)) {
            throw new MalformedLessonException($path, 'Mini Quiz section must contain a fenced yaml code block.');
        }

        try {
            $items = Yaml::parse($match[1]);
        } catch (\Throwable $exception) {
            throw new MalformedLessonException($path, 'Invalid YAML in Mini Quiz block: '.$exception->getMessage());
        }

        if (!is_array($items) || [] === $items) {
            throw new MalformedLessonException($path, 'Mini Quiz block must contain at least one question.');
        }

        $questions = [];
        foreach ($items as $item) {
            if (!isset($item['question'], $item['options'], $item['answer']) || !is_array($item['options'])) {
                throw new MalformedLessonException($path, 'Each quiz question needs "question", "options" and "answer".');
            }

            if ($item['answer'] < 0 || $item['answer'] >= count($item['options'])) {
                throw new MalformedLessonException($path, 'Quiz "answer" index is out of range for its options.');
            }

            $questions[] = new QuizQuestion(
                question: (string) $item['question'],
                options: array_map('strval', $item['options']),
                answer: (int) $item['answer'],
                explanation: (string) ($item['explanation'] ?? ''),
            );
        }

        return $questions;
    }
}
