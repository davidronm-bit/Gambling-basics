<?php
function initializeSession() {
    if (!isset($_SESSION['score'])) {
        $_SESSION['score'] = 100;
        $_SESSION['history'] = [];
        $_SESSION['showWelcome'] = true;
        $_SESSION['pendingRolls'] = [];
        $_SESSION['error'] = null;
    }
}

function handleReset() {
    $_SESSION['score'] = 100;
    $_SESSION['history'] = [];
    $_SESSION['showWelcome'] = true;
    $_SESSION['pendingRolls'] = [];
    $_SESSION['error'] = null;
    
    if (isAjaxRequest()) {
        sendJsonResponse(['success' => true, 'score' => 100]);
    }
    
    redirectToSelf();
}

function handlePlay($game) {
    $_SESSION['showWelcome'] = false;
    
    $betType = $_POST['bet_type'] ?? 'pattern';
    $betValue = $_POST['bet_value'] ?? 'odd';
    $betAmount = isset($_POST['bet']) ? (float)$_POST['bet'] : 1;
    
    $currentScore = $_SESSION['score'];
    $error = validateBet($currentScore, $betAmount);
    
    if ($error) {
        $_SESSION['error'] = $error;
        
        if (isAjaxRequest()) {
            sendJsonResponse(['error' => $error]);
        }
        
        redirectToSelf();
        return;
    }
    
    $dice = $game->rollDice();
    $result = $game->calculateResult($betType, $betValue, $betAmount, $dice['total']);
    
    $newScore = $currentScore - $betAmount;
    if ($result['win']) {
        $newScore += $result['points'];
    }
    
    $historyEntry = $game->createHistoryEntry(
        $betType, $betValue, $betAmount, $dice, 
        $dice['total'], $result['win'], $result['points']
    );
    
    $_SESSION['pendingRolls'][] = [
        'history_entry' => $historyEntry,
        'new_score' => $newScore,
        'display_data' => [
            'die1' => $dice['die1'],
            'die2' => $dice['die2'],
            'total' => $dice['total'],
            'win' => $result['win'],
            'points' => $result['points'],
            'bet_type' => $betType,
            'bet_value' => $betValue,
            'bet_amount' => $betAmount,
            'pattern_display' => $game->getBetLabel($betType, $betValue)
        ]
    ];
    
    redirectToSelf();
}

function handleConfirmRoll($game) {
    $response = ['success' => false, 'new_score' => null];
    
    if (!empty($_SESSION['pendingRolls'])) {
        $pending = array_shift($_SESSION['pendingRolls']);
        
        $_SESSION['history'] = $game->updateHistory(
            $_SESSION['history'], 
            $pending['history_entry']
        );
        $_SESSION['score'] = $pending['new_score'];
        
        $response = [
            'success' => true,
            'new_score' => $pending['new_score']
        ];
    }
    
    sendJsonResponse($response);
}

function validateBet($currentScore, $betAmount) {
    if ($currentScore <= 0) {
        return [
            'message' => 'Game Over. You have 0 credits. Reset to continue.',
            'type' => 'gameover'
        ];
    }
    
    if ($betAmount <= 0) {
        return [
            'message' => 'Invalid bet amount. Enter a stake greater than 0.',
            'type' => 'invalid'
        ];
    }
    
    if ($betAmount > $currentScore) {
        return [
            'message' => "Insufficient balance. You have $currentScore credits.",
            'type' => 'insufficient',
            'current_balance' => $currentScore,
            'bet_amount' => $betAmount
        ];
    }
    
    return null;
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirectToSelf() {
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function getDiceImage($number) {
    $images = [
        1 => 'img/dice-six-faces-one.svg',
        2 => 'img/dice-six-faces-two.svg',
        3 => 'img/dice-six-faces-three.svg',
        4 => 'img/dice-six-faces-four.svg',
        5 => 'img/dice-six-faces-five.svg',
        6 => 'img/dice-six-faces-six.svg',
    ];
    return $images[$number] ?? $images[1];
}

function getCurrentDisplayData() {
    if (!empty($_SESSION['pendingRolls'])) {
        $lastRoll = end($_SESSION['pendingRolls']);
        return $lastRoll['display_data'];
    }
    return null;
}

function getErrorMessage() {
    $error = $_SESSION['error'] ?? null;
    unset($_SESSION['error']);
    return $error;
}