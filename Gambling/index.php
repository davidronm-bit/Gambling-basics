<?php
session_start();
require_once 'includes/Game.php';
require_once 'includes/functions.php';

$game = new DiceGame();
initializeSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'reset':
            handleReset();
            break;
        case 'play':
            handlePlay($game);
            break;
        case 'confirm_roll':
            handleConfirmRoll($game);
            break;
    }
}

$score = $_SESSION['score'];
$history = $_SESSION['history'];
$showWelcome = $_SESSION['showWelcome'];
$displayRoll = getCurrentDisplayData();
$error = getErrorMessage();

$diceImages = [
    1 => 'img/dice-six-faces-one.svg',
    2 => 'img/dice-six-faces-two.svg',
    3 => 'img/dice-six-faces-three.svg',
    4 => 'img/dice-six-faces-four.svg',
    5 => 'img/dice-six-faces-five.svg',
    6 => 'img/dice-six-faces-six.svg',
];

$die1 = $displayRoll ? $displayRoll['die1'] : 1;
$die2 = $displayRoll ? $displayRoll['die2'] : 1;
$total = $displayRoll ? $displayRoll['total'] : null;
$win = $displayRoll ? $displayRoll['win'] : null;
$points = $displayRoll ? $displayRoll['points'] : null;
$betAmount = $displayRoll ? $displayRoll['bet_amount'] : null;
$betType = $displayRoll ? $displayRoll['bet_type'] : 'pattern';
$betValue = $displayRoll ? $displayRoll['bet_value'] : 'odd';
$patternDisplay = $displayRoll ? $displayRoll['pattern_display'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dice Betting Game</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="fake-ad fake-ad-left" aria-hidden="true">
        <div class="ad-pill">ADVERTISEMENT</div>
        <div class="ad-content">
            <div class="ad-title">Get 200% bonus</div>
            <div class="ad-sub">Use code <strong>ROLLFAST</strong> at checkout</div>
            <div class="ad-cta">Claim now</div>
        </div>
    </div>
    
    <div class="fake-ad fake-ad-right" aria-hidden="true">
        <div class="ad-pill">SPONSORED</div>
        <div class="ad-content">
            <div class="ad-title">VIP Boost</div>
            <div class="ad-sub">Join the club for extra spins</div>
            <div class="ad-cta">Join now</div>
        </div>
    </div>
    
    <main class="container">
        <?php if ($showWelcome && !$displayRoll && !$error): ?>
            <div class="message message-welcome">
                Welcome to Dice Betting Game! Place your bet and roll the dice.
            </div>
        <?php endif; ?>
        
        <div class="main-layout">
            <div class="left-side">
                <div class="dice-section">
                    <div class="section-header">
                        <h2>Dice wheel</h2>
                        <div class="score">Score: <strong id="scoreValue"><?php echo $score; ?></strong></div>
                    </div>
                    
                    <div class="dice-container" id="diceContainer">
                        <img id="die1" class="die" src="<?php echo getDiceImage($die1); ?>" alt="Die" data-final="<?php echo $die1; ?>">
                        <img id="die2" class="die" src="<?php echo getDiceImage($die2); ?>" alt="Die" data-final="<?php echo $die2; ?>">
                    </div>
                    
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet">
                                <?php echo $displayRoll ? htmlspecialchars($patternDisplay) . ' (' . $betAmount . ' credits)' : '-'; ?>
                            </span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Roll total:</span>
                            <span class="result-value" id="rollTotal" data-final="<?php echo $total !== null ? $total : '0'; ?>">
                                <?php echo $total !== null ? $total : '-'; ?>
                            </span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText" data-win="<?php echo $win === true ? 'win' : ($win === false ? 'lose' : ''); ?>" data-points="<?php echo $points !== null ? $points : 0; ?>">
                                <?php if ($win === true): ?>
                                    WIN (+<?php echo $points; ?>)
                                <?php elseif ($win === false): ?>
                                    LOSE
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="bottom-section">
                    <div class="betting-settings">
                        <div class="section-header">
                            <h2>Betting settings</h2>
                        </div>
                        
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">Pattern bets <span class="multiplier">2x</span></div>
                                <div class="pattern-grid">
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="odd" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'odd') ? 'checked' : ''; ?>>
                                        <span>Odd</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="even" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'even') ? 'checked' : ''; ?>>
                                        <span>Even</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="low" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'low') ? 'checked' : ''; ?>>
                                        <span>Low (2-6)</span>
                                    </label>
                                    <label class="bet-option">
                                        <input type="radio" name="betPattern" value="high" class="pattern-radio" <?php echo (!$displayRoll && $betType === 'pattern' && $betValue === 'high') ? 'checked' : ''; ?>>
                                        <span>High (7-12)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="bet-group">
                                <div class="group-title">Exact number <span class="multiplier">10x</span></div>
                                <div class="number-grid">
                                    <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <button type="button" class="number-btn <?php echo ($displayRoll && $betType === 'number' && $betValue == $i) ? 'active' : ''; ?>" data-number="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        
                        <div class="actions-content">
                            <div class="stake-group">
                                <div class="group-title">Stake amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                </div>
                                <input id="betStake" type="number" class="stake-input" step="1" value="20">
                            </div>
                            
                            <div class="action-group">
                                <button id="placeBetBtn" class="btn-primary">Roll Dice</button>
                                <button id="resetBtn" class="btn-danger">Reset Game</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Roll history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    
                    <div class="history-list-container">
                        <ul class="history-list" id="historyList">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No rounds yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $round): ?>
                                    <li class="history-item">
                                        <div class="history-bet">
                                            <strong><?php echo htmlspecialchars($round['pattern']); ?></strong> x<?php echo $round['bet']; ?>
                                        </div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN' : 'LOSE'; ?> +<?php echo $round['points']; ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php echo $round['dice'][0]; ?> + <?php echo $round['dice'][1]; ?> = <?php echo $round['total']; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <form id="gameForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="formAction" value="play">
        <input type="hidden" name="bet_type" id="formBetType" value="pattern">
        <input type="hidden" name="bet_value" id="formBetValue" value="odd">
        <input type="hidden" name="bet" id="formBet" value="20">
    </form>
    
    <script>
        window.shouldAnimate = <?php echo $displayRoll ? 'true' : 'false'; ?>;
        window.rollData = <?php echo json_encode($displayRoll); ?>;
        window.currentScore = <?php echo $score; ?>;
        window.hasPendingRolls = <?php echo !empty($_SESSION['pendingRolls']) ? 'true' : 'false'; ?>;
        window.error = <?php echo json_encode($error); ?>;
    </script>
    
    <script src="script.js"></script>
</body>
</html>