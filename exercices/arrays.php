<?php
$scores = [88, 95, 73, 60];

echo "Number of scores: " . count($scores) . "\n";

foreach($scores as $score){
    echo "Score: $score \n";
} 
$total = 0;  
for($i=0; $i < count($scores); $i++){
    $total += $scores[$i];
}
echo "Total: $total \n";