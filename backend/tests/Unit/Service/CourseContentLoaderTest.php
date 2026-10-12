<?php

namespace App\Tests\Unit\Service;

use App\Exception\LessonNotFoundException;
use App\Service\CourseContentLoader;
use PHPUnit\Framework\TestCase;

class CourseContentLoaderTest extends TestCase
{
    private function loader(): CourseContentLoader
    {
        return new CourseContentLoader(__DIR__.'/../../Fixtures/courses');
    }

    public function testValidLessonLoadsWithParsedSectionsAndQuiz(): void
    {
        $lesson = $this->loader()->getLesson('fixture-valid-lesson');

        self::assertSame('Fixture Valid Lesson', $lesson->title);
        self::assertSame('php', $lesson->track);
        self::assertSame('php-fundamentals', $lesson->chapter);
        self::assertSame('Explanation text.', $lesson->sections['explanation']);
        self::assertSame('echo "hello";', $lesson->sections['code']);
        self::assertSame('php', $lesson->sections['codeLanguage']);
        self::assertCount(1, $lesson->quiz);
        self::assertSame('What does this fixture test?', $lesson->quiz[0]->question);
    }

    public function testMetadataIsParsedFromFrontmatter(): void
    {
        $lesson = $this->loader()->getLesson('fixture-valid-lesson');

        self::assertSame('beginner', $lesson->difficulty);
        self::assertSame(5, $lesson->estimatedMinutes);
        self::assertSame(['fixture'], $lesson->tags);
        self::assertSame(1, $lesson->order);
    }

    public function testLessonsAreOrderedAndNavigationLinksAreCorrect(): void
    {
        $loader = $this->loader();

        $first = $loader->getLesson('fixture-valid-lesson');
        $second = $loader->getLesson('fixture-valid-lesson-two');

        self::assertNull($first->previous);
        self::assertNotNull($first->next);
        self::assertSame('fixture-valid-lesson-two', $first->next->slug);

        self::assertNotNull($second->previous);
        self::assertSame('fixture-valid-lesson', $second->previous->slug);
    }

    public function testCourseTreeGroupsLessonsByTrackAndChapter(): void
    {
        $tracks = $this->loader()->getCourseTree();

        self::assertCount(1, $tracks);
        self::assertSame('php', $tracks[0]->key);
        self::assertCount(1, $tracks[0]->chapters);
        self::assertSame('php-fundamentals', $tracks[0]->chapters[0]->key);
        // The malformed fixture must never appear in the tree.
        self::assertCount(2, $tracks[0]->chapters[0]->lessons);
    }

    public function testMissingLessonThrowsLessonNotFoundException(): void
    {
        $this->expectException(LessonNotFoundException::class);

        $this->loader()->getLesson('does-not-exist');
    }

    public function testMalformedLessonIsSkippedAndReportedInsteadOfCrashing(): void
    {
        $loader = $this->loader();

        // Scanning must not throw even though one file is malformed.
        $tracks = $loader->getCourseTree();
        self::assertNotEmpty($tracks);

        $malformed = $loader->getMalformedFiles();
        self::assertCount(1, $malformed);

        $reason = array_values($malformed)[0];
        self::assertStringContainsString('Missing required section', $reason);
    }

    public function testMalformedLessonIsNotReachableBySlug(): void
    {
        $this->expectException(LessonNotFoundException::class);

        $this->loader()->getLesson('fixture-malformed-lesson');
    }

    public function testSearchFindsLessonByContent(): void
    {
        $results = $this->loader()->search('hello two');

        self::assertNotEmpty($results);
        self::assertSame('fixture-valid-lesson-two', $results[0]['lesson']->slug);
    }

    public function testSearchReturnsEmptyArrayForBlankQuery(): void
    {
        self::assertSame([], $this->loader()->search('   '));
    }
}
