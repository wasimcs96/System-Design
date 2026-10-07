<?php

$nums = [-1,0,1,2,-1,-4]; sort($nums);
$result = [];
for($i=0; $i < count($nums); $i++){
    $hashmap = [];
    if($i>0 && $nums[$i] == $nums[$i-1]) continue;
    
    $left = $i+1;
    $right = count($nums)-1;

    while($left < $right){
        $sum = $nums[$i] + $nums[$left]  + $nums[$right];
        if($sum > 0) 
                $right--;
        elseif($sum < 0) 
                $left++;
        else{
            $result[] = [$nums[$i], $nums[$left], $nums[$right]];
            $left++;
            $right--;
            while($left < $right && $nums[$left] == $nums[$left-1]) $left++;
            while($left < $right && $nums[$right] == $nums[$right+1]) $right++;
        }
    }

}

print_r($result);