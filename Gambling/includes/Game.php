<?php
class DiceGame {
    private $patterns;
    
    public function __construct() {
        $this->patterns = [
            'odd' => ['label' => 'Odd', 'multiplier' => 2],
            'even' => ['label' => 'Even', 'multiplier' => 2],
            'low' => ['label' => 'Low (2-6)', 'multiplier' => 2],
            'high' => ['label' => 'High (7-12)', 'multiplier' => 2],
        ];
    }
    
    public function rollDice() {
        $die1 = random_int(1, 6);
        $die2 = random_int(1, 6);
        return [
            'die1' => $die1,
            'die2' => $die2,
            'total' => $die1 + $die2
        ];
    }
    
    public function calculateResult($betType, $betValue, $betAmount, $total) {
        $win = false;
        $multiplier = 1;
        
        if ($betType === 'number') {
            $win = $total === (int)$betValue;
            $multiplier = 10;
        } else {
            switch ($betValue) {
                case 'odd':
                    $win = $total % 2 !== 0;
                    break;
                case 'even':
                    $win = $total % 2 === 0;
                    break;
                case 'low':
                    $win = $total >= 2 && $total <= 6;
                    break;
                case 'high':
                    $win = $total >= 7 && $total <= 12;
                    break;
            }
            $multiplier = $this->patterns[$betValue]['multiplier'];
        }
        
        $points = $win ? (int)round($betAmount * $multiplier) : 0;
        
        return [
            'win' => $win,
            'points' => $points,
            'multiplier' => $multiplier
        ];
    }
    
    public function getBetLabel($betType, $betValue) {
        if ($betType === 'number') {
            return "Number $betValue";
        }
        return $this->patterns[$betValue]['label'];
    }
    
    public function createHistoryEntry($betType, $betValue, $betAmount, $dice, $total, $win, $points) {
        return [
            'timestamp' => date('c'),
            'pattern' => $this->getBetLabel($betType, $betValue),
            'bet' => $betAmount,
            'dice' => [$dice['die1'], $dice['die2']],
            'total' => $total,
            'win' => $win,
            'points' => $points
        ];
    }
    
    public function updateHistory($history, $newEntry) {
        array_unshift($history, $newEntry);
        return array_values(array_slice($history, 0, 10));
    }
    
    public function getPatterns() {
        return $this->patterns;
    }
}