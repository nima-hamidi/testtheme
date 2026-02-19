/**
 * Novel Quiz JavaScript
 * شروع مسابقه، نمایش سوالات، شمارش معکوس، ارسال پاسخ، نتیجه
 * 
 * @package suspended developer
 * @since 3.0.0
 */
(function($) {
    'use strict';

    const QuizApp = {
        attemptId: null,
        quizId: null,
        timer: null,
        timeLeft: 0,

        init() {
            $(document).on('click', '.novel-quiz-start-btn', (e) => {
                e.preventDefault();
                this.quizId = $(e.currentTarget).data('quiz-id');
                if (confirm(NovelQuiz.strings.confirm_start)) this.startQuiz();
            });
        },

        startQuiz() {
            const $lobby = $('#quizLobby');
            const $play = $('#quizPlayArea');

            $.post(NovelQuiz.ajaxurl, {
                action: 'novel_start_quiz',
                nonce: NovelQuiz.nonce,
                quiz_id: this.quizId,
            }, (r) => {
                if (r.success) {
                    this.attemptId = r.data.attempt_id;
                    $lobby.find('.novel-quiz-lobby__header, .novel-quiz-lobby__info, .novel-quiz-lobby__rewards, .novel-quiz-lobby__stats, .novel-quiz-start-btn, .novel-quiz-lobby__leaderboard').hide();
                    this.renderQuestion(r.data.question, r.data.question_index, r.data.total_questions, r.data.current_score);
                    $play.show();
                } else {
                    this.toast(r.data.message || NovelQuiz.strings.error, 'error');
                }
            });
        },

        renderQuestion(q, index, total, score) {
            const $play = $('#quizPlayArea');
            const pct = ((index) / total) * 100;

            let optionsHtml = '';
            for (const [key, text] of Object.entries(q.options)) {
                optionsHtml += `
                    <button class="novel-quiz-option" data-answer="${key}">
                        <span class="novel-quiz-option__key">${key.toUpperCase()}</span>
                        <span class="novel-quiz-option__text">${this.esc(text)}</span>
                    </button>`;
            }

            $play.html(`
                <div class="novel-quiz-play__header">
                    <span>سوال ${index + 1} از ${total}</span>
                    <span class="novel-quiz-play__score">${NovelQuiz.strings.points}: ${score}</span>
                </div>
                <div class="novel-quiz-play__progress"><div class="novel-quiz-play__progress-bar" style="width:${pct}%"></div></div>
                <div class="novel-quiz-play__timer" id="quizTimer">
                    <svg class="novel-quiz-timer-svg" viewBox="0 0 120 120">
                        <circle class="novel-quiz-timer-bg" cx="60" cy="60" r="54"/>
                        <circle class="novel-quiz-timer-fg" cx="60" cy="60" r="54" id="timerCircle"/>
                    </svg>
                    <span class="novel-quiz-timer-text" id="timerText">${q.time}</span>
                </div>
                <div class="novel-quiz-play__question">
                    <h3>${this.esc(q.text)}</h3>
                    ${q.reference ? `<small>📖 مرجع: قسمت ${q.reference}</small>` : ''}
                </div>
                <div class="novel-quiz-play__options" id="quizOptions" data-question-id="${q.id}">
                    ${optionsHtml}
                </div>
            `);

            this.startTimer(q.time, q.id);

            // Bind option click
            $play.find('.novel-quiz-option').on('click', (e) => {
                const answer = $(e.currentTarget).data('answer');
                this.submitAnswer(q.id, answer);
            });
        },

        startTimer(seconds, questionId) {
            this.timeLeft = seconds;
            const circumference = 2 * Math.PI * 54;
            const $circle = $('#timerCircle');
            const $text = $('#timerText');
            $circle.css('stroke-dasharray', circumference);

            clearInterval(this.timer);
            const startTime = Date.now();

            this.timer = setInterval(() => {
                const elapsed = Math.floor((Date.now() - startTime) / 1000);
                this.timeLeft = Math.max(0, seconds - elapsed);

                $text.text(this.timeLeft);
                const offset = circumference * (1 - this.timeLeft / seconds);
                $circle.css('stroke-dashoffset', offset);

                // Colors
                if (this.timeLeft <= 10) $circle.css('stroke', '#ef4444');
                else if (this.timeLeft <= 20) $circle.css('stroke', '#f59e0b');
                else $circle.css('stroke', '#10b981');

                if (this.timeLeft <= 0) {
                    clearInterval(this.timer);
                    this.submitAnswer(questionId, ''); // timeout
                }
            }, 200);
        },

        submitAnswer(questionId, answer) {
            clearInterval(this.timer);
            const timeSpent = parseInt($('#quizTimer').closest('.novel-quiz-play__header').next().find('.novel-quiz-play__progress-bar').length ? 0 : 0);
            const actualTime = Math.max(0, parseInt($('#timerText').text() || 0));
            const totalTime = 30; // fallback
            const spent = totalTime - actualTime;

            $('#quizOptions .novel-quiz-option').off('click').css('pointer-events', 'none');

            $.post(NovelQuiz.ajaxurl, {
                action: 'novel_answer_question',
                nonce: NovelQuiz.nonce,
                attempt_id: this.attemptId,
                question_id: questionId,
                answer: answer,
                time_spent: spent,
            }, (r) => {
                if (r.success) {
                    this.showAnswerFeedback(r.data);
                } else {
                    this.toast(r.data.message || NovelQuiz.strings.error, 'error');
                }
            });
        },

        showAnswerFeedback(data) {
            const $options = $('#quizOptions');

            // Highlight correct/wrong
            $options.find('.novel-quiz-option').each(function() {
                const key = $(this).data('answer');
                if (key === data.correct_answer) $(this).addClass('is-correct');
                if (key !== data.correct_answer && $(this).hasClass('clicked')) $(this).addClass('is-wrong');
            });

            // Feedback text
            const feedbackClass = data.is_correct ? 'is-correct' : 'is-wrong';
            const feedbackText = data.is_correct
                ? `${NovelQuiz.strings.correct} +${data.points_earned} ${NovelQuiz.strings.points}`
                : (data.points_earned === undefined ? NovelQuiz.strings.timeout : NovelQuiz.strings.wrong);

            $options.after(`
                <div class="novel-quiz-feedback ${feedbackClass}">
                    <p>${feedbackText}</p>
                    ${data.explanation ? `<p class="novel-quiz-feedback__explain">💡 ${this.esc(data.explanation)}</p>` : ''}
                </div>
            `);

            // Next or finish
            setTimeout(() => {
                if (data.is_last) {
                    this.finishQuiz();
                } else {
                    const q = data.next_question;
                    this.renderQuestion(q, data.question_index, data.question_index + 2, data.current_score);
                }
            }, 2500);
        },

        finishQuiz() {
            $.post(NovelQuiz.ajaxurl, {
                action: 'novel_finish_quiz',
                nonce: NovelQuiz.nonce,
                attempt_id: this.attemptId,
            }, (r) => {
                if (r.success) this.showResult(r.data);
            });
        },

        showResult(data) {
            $('#quizPlayArea').hide();
            const $result = $('#quizResultArea');
            const isGreat = data.percentage >= 80;

            let lbHtml = '';
            if (data.leaderboard) {
                data.leaderboard.forEach(l => {
                    const icons = {1:'🥇',2:'🥈',3:'🥉'};
                    lbHtml += `<div class="novel-quiz-lb-item ${l.rank === data.rank ? 'is-me' : ''}">
                        <span class="novel-quiz-lb-rank">${icons[l.rank] || l.rank}</span>
                        <span class="novel-quiz-lb-name">${this.esc(l.name)}</span>
                        <span class="novel-quiz-lb-score">${l.score}</span>
                        <span class="novel-quiz-lb-coins">🪙 ${l.coins}</span>
                    </div>`;
                });
            }

            let answersHtml = '';
            if (data.answers) {
                data.answers.forEach((a, i) => {
                    answersHtml += `<div class="novel-quiz-review-item ${a.is_correct ? 'is-correct' : 'is-wrong'}">
                        <span>${i+1}. ${a.is_correct ? '✅' : '❌'} ${this.esc(a.question)}</span>
                        <span>+${a.points} ${NovelQuiz.strings.points}</span>
                    </div>`;
                });
            }

            $result.html(`
                <div class="novel-quiz-result__inner">
                    <h2>🎉 مسابقه تمام شد!</h2>
                    <div class="novel-quiz-result__score-card">
                        <div class="novel-quiz-result__score">${data.score} / ${data.total_points}</div>
                        <div class="novel-quiz-result__details">
                            ✅ ${data.correct} صحیح &nbsp; ❌ ${data.wrong} غلط &nbsp;
                            ⏱ ${Math.floor(data.time_spent/60)}:${String(data.time_spent%60).padStart(2,'0')} &nbsp;
                            📊 رتبه ${data.rank} از ${data.total_participants} &nbsp;
                            🪙 ${data.coins} سکه
                        </div>
                        <div class="novel-quiz-result__pct-bar"><div style="width:${data.percentage}%"></div></div>
                        <span>${data.percentage}٪</span>
                    </div>
                    <div class="novel-quiz-result__lb"><h3>📊 لیدربورد</h3>${lbHtml}</div>
                    <div class="novel-quiz-result__review"><h3>📋 بررسی پاسخ‌ها</h3>${answersHtml}</div>
                    <div class="novel-quiz-result__actions">
                        <a href="/" class="novel-btn novel-btn--primary">🏠 صفحه اصلی</a>
                    </div>
                </div>
            `).show();
        },

        toast(msg, type) {
            if (typeof NovelApp !== 'undefined' && NovelApp.showToast) NovelApp.showToast(msg, type);
            else alert(msg);
        },
        esc(t) { const d = document.createElement('div'); d.textContent = t||''; return d.innerHTML; }
    };

    $(document).ready(() => QuizApp.init());
})(jQuery);