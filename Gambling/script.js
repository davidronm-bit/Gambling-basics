let rollingInterval = null;
let rollCount = 0;
const MAX_ROLLS = 676767676767;
let isRolling = false;
let currentBetType = 'pattern';
let currentBetValue = 'odd';

const DICE_IMAGES = {
    1: 'img/dice-six-faces-one.svg',
    2: 'img/dice-six-faces-two.svg',
    3: 'img/dice-six-faces-three.svg',
    4: 'img/dice-six-faces-four.svg',
    5: 'img/dice-six-faces-five.svg',
    6: 'img/dice-six-faces-six.svg'
};

const PATTERN_NAMES = {
    'odd': 'Odd',
    'even': 'Even',
    'low': 'Low (2-6)',
    'high': 'High (7-12)'
};

function randomDiceFace() {
    return Math.floor(Math.random() * 6) + 1;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function addToHistory(pattern, bet, dice1, dice2, total, win, points) {
    const historyList = document.getElementById('historyList');
    const emptyItem = historyList.querySelector('.empty');
    
    if (emptyItem) {
        emptyItem.remove();
    }
    
    const historyItem = document.createElement('li');
    historyItem.className = 'history-item';
    historyItem.innerHTML = `
        <div class="history-bet">
            <strong>${escapeHtml(pattern)}</strong> x${bet}
        </div>
        <div class="history-result ${win ? 'win' : 'lose'}">
            ${win ? 'WIN' : 'LOSE'} +${points}
        </div>
        <div class="history-dice">
            ${dice1} + ${dice2} = ${total}
        </div>
    `;
    
    historyList.insertBefore(historyItem, historyList.firstChild);
    
    while (historyList.children.length > 10) {
        historyList.removeChild(historyList.lastChild);
    }
}

function confirmRollToServer() {
    const formData = new FormData();
    formData.append('action', 'confirm_roll');
    
    return fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('scoreValue').textContent = data.new_score;
            
            if (data.new_score <= 0) {
                showGameOverNotification();
            }
        }
        return data;
    })
    .catch(error => {
        console.error('Error confirming roll:', error);
        return { success: false };
    });
}

function animateDiceRoll(finalDice1, finalDice2, finalTotal, finalWin, finalPoints, pattern, betAmount) {
    const die1Img = document.getElementById('die1');
    const die2Img = document.getElementById('die2');
    const rollTotalEl = document.getElementById('rollTotal');
    const resultTextEl = document.getElementById('resultText');
    const diceContainer = document.getElementById('diceContainer');
    
    if (rollingInterval) {
        clearInterval(rollingInterval);
    }
    
    diceContainer.classList.add('rolling');
    isRolling = true;
    rollCount = 0;
    
    rollingInterval = setInterval(() => {
        const random1 = randomDiceFace();
        const random2 = randomDiceFace();
        const randomTotal = random1 + random2;
        
        die1Img.src = DICE_IMAGES[random1];
        die2Img.src = DICE_IMAGES[random2];
        rollTotalEl.textContent = randomTotal;
        resultTextEl.textContent = 'Rolling...';
        resultTextEl.classList.remove('win', 'lose');
        
        rollCount++;
        
        if (rollCount >= MAX_ROLLS) {
            clearInterval(rollingInterval);
            rollingInterval = null;
            
            setTimeout(() => {
                diceContainer.classList.remove('rolling');
                die1Img.src = DICE_IMAGES[finalDice1];
                die2Img.src = DICE_IMAGES[finalDice2];
                rollTotalEl.textContent = finalTotal;
                
                if (finalWin) {
                    resultTextEl.textContent = `WIN (+${finalPoints})`;
                    resultTextEl.classList.add('win');
                } else {
                    resultTextEl.textContent = 'LOSE';
                    resultTextEl.classList.add('lose');
                }
                
                addToHistory(pattern, betAmount, finalDice1, finalDice2, finalTotal, finalWin, finalPoints);
                
                confirmRollToServer().then(() => {
                    const placeBtn = document.getElementById('placeBetBtn');
                    if (placeBtn) {
                        placeBtn.disabled = false;
                        placeBtn.textContent = 'Roll Dice';
                    }
                    isRolling = false;
                });
            }, 2500);
        }
    }, 100);
}

function updateBetDisplay() {
    const displayBet = document.getElementById('displayBet');
    const stake = document.getElementById('betStake').value;
    
    if (currentBetType === 'number') {
        displayBet.textContent = `Number ${currentBetValue} (${stake} credits)`;
    } else {
        displayBet.textContent = `${PATTERN_NAMES[currentBetValue]} (${stake} credits)`;
    }
}

function showErrorNotification(error) {
    if (!error) return;
    
    const handlers = {
        insufficient: () => {
            Swal.fire({
                title: 'Insufficient Balance',
                html: `You have <strong>${error.current_balance}</strong> credits, but you bet <strong>${error.bet_amount}</strong> credits.`,
                icon: 'error',
                confirmButtonText: 'Adjust Bet',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    const betStake = document.getElementById('betStake');
                    betStake.value = error.current_balance;
                    updateBetDisplay();
                }
            });
        },
        gameover: () => {
            Swal.fire({
                title: 'Game Over',
                text: error.message,
                icon: 'error',
                confirmButtonText: 'Reset Game',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) resetGame();
            });
        },
        invalid: () => {
            Swal.fire({
                title: 'Invalid Bet Amount',
                text: error.message,
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then(() => {
                const betStake = document.getElementById('betStake');
                betStake.focus();
                betStake.value = 1;
                updateBetDisplay();
            });
        }
    };
    
    const handler = handlers[error.type] || handlers.invalid;
    handler();
}

function showGameOverNotification() {
    Swal.fire({
        title: 'Game Over',
        text: 'You have 0 credits left. Reset the game to continue playing.',
        icon: 'error',
        confirmButtonText: 'Reset Game',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    }).then(result => {
        if (result.isConfirmed) resetGame();
    });
}

function submitBet() {
    if (isRolling) {
        Swal.fire({
            title: 'Wait',
            text: 'The dice are still rolling. Please wait a moment.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
        return false;
    }
    
    const betAmount = parseFloat(document.getElementById('betStake').value);
    const currentScore = parseInt(document.getElementById('scoreValue').textContent);
    
    if (isNaN(betAmount) || betAmount <= 0) {
        Swal.fire({
            title: 'Invalid Bet Amount',
            text: 'Please enter a stake amount greater than 0.',
            icon: 'warning',
            confirmButtonText: 'OK'
        }).then(() => {
            const betStake = document.getElementById('betStake');
            betStake.focus();
            betStake.value = 1;
            updateBetDisplay();
        });
        return false;
    }
    
    if (currentScore <= 0) {
        showGameOverNotification();
        return false;
    }
    
    if (betAmount > currentScore) {
        Swal.fire({
            title: 'Insufficient Balance',
            html: `You have <strong>${currentScore}</strong> credits, but you bet <strong>${betAmount}</strong> credits.`,
            icon: 'error',
            confirmButtonText: 'Adjust Bet',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                const betStake = document.getElementById('betStake');
                betStake.value = currentScore;
                updateBetDisplay();
            }
        });
        return false;
    }
    
    document.getElementById('formBetType').value = currentBetType;
    document.getElementById('formBetValue').value = currentBetValue;
    document.getElementById('formBet').value = betAmount;
    document.getElementById('formAction').value = 'play';
    
    const placeBtn = document.getElementById('placeBetBtn');
    if (placeBtn) {
        placeBtn.disabled = true;
        placeBtn.textContent = 'Rolling...';
    }
    
    document.getElementById('gameForm').submit();
    return true;
}

function resetGame() {
    Swal.fire({
        title: 'Reset Game',
        text: 'Your score will be reset to 100 credits and all history will be cleared.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e64545',
        cancelButtonColor: '#4ac47d',
        confirmButtonText: 'Yes, reset it',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('formAction').value = 'reset';
            document.getElementById('gameForm').submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const patternRadios = document.querySelectorAll('.pattern-radio');
    const numberBtns = document.querySelectorAll('.number-btn');
    const quickStakes = document.querySelectorAll('.quick-stake');
    const placeBtn = document.getElementById('placeBetBtn');
    const resetBtn = document.getElementById('resetBtn');
    const betStake = document.getElementById('betStake');
    
    if (window.error) {
        showErrorNotification(window.error);
    }
    
    if (window.currentScore <= 0 && !window.hasPendingRolls) {
        showGameOverNotification();
    }
    
    const savedBetType = document.getElementById('formBetType').value;
    const savedBetValue = document.getElementById('formBetValue').value;
    
    if (savedBetType === 'number') {
        currentBetType = 'number';
        currentBetValue = savedBetValue;
        const activeBtn = document.querySelector(`.number-btn[data-number="${savedBetValue}"]`);
        if (activeBtn) activeBtn.classList.add('active');
    } else {
        currentBetType = 'pattern';
        currentBetValue = savedBetValue;
        const activeRadio = document.querySelector(`.pattern-radio[value="${savedBetValue}"]`);
        if (activeRadio) activeRadio.checked = true;
    }
    
    if (!window.shouldAnimate) {
        updateBetDisplay();
    }
    
    patternRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && !isRolling) {
                numberBtns.forEach(btn => btn.classList.remove('active'));
                currentBetType = 'pattern';
                currentBetValue = this.value;
                updateBetDisplay();
            }
        });
    });
    
    numberBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isRolling) {
                numberBtns.forEach(b => b.classList.remove('active'));
                patternRadios.forEach(r => r.checked = false);
                this.classList.add('active');
                currentBetType = 'number';
                currentBetValue = this.getAttribute('data-number');
                updateBetDisplay();
            }
        });
    });
    
    quickStakes.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!isRolling) {
                const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                const maxBet = parseInt(document.getElementById('scoreValue').textContent);
                let newValue = multiplier === 1 ? maxBet : Math.floor(maxBet * multiplier);
                if (newValue < 1) newValue = 1;
                betStake.value = newValue;
                updateBetDisplay();
            }
        });
    });
    
    betStake.addEventListener('input', function() {
        if (!isRolling) updateBetDisplay();
    });
    
    placeBtn.addEventListener('click', submitBet);
    resetBtn.addEventListener('click', resetGame);
    
    if (window.shouldAnimate && window.rollData) {
        const data = window.rollData;
        animateDiceRoll(
            data.die1, data.die2, data.total, data.win, data.points,
            data.pattern_display, data.bet_amount
        );
    }
});