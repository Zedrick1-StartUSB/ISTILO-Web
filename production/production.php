<?php
session_start();
require "../logout.php";

if($_SESSION["role"] != "production"){
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f5ef">
    <title>Production Desk</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="production.css">
</head>
<body>
    <div class="production-app">
        <aside class="production-sidebar">
            <a class="brand" href="#dashboard"><span class="brand-stamp">p.</span><span>made &amp; stitched<small>PRODUCTION DESK</small></span></a>

            <p class="side-label">YOUR WORK</p>
            <nav class="production-nav" aria-label="Production workspace">
                <a class="nav-item active" href="#dashboard" aria-current="page"><span>⌂</span> Dashboard</a>
                <a class="nav-item" href="#incoming"><span>▤</span> Incoming work <b>3</b></a>
                <a class="nav-item" href="#finished"><span>✓</span> Finished work</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>

            <div class="sidebar-note">
                <span>✿</span>
                <p><strong>One stitch at a time.</strong><br>You're making good things happen.</p>
            </div>

            <div class="maker-profile">
                <span class="maker-avatar">MR</span>
                <span><strong>Morgan Reed</strong><small>Production maker</small></span>
            </div>
        </aside>

        <main class="production-main">
            <section class="screen-section" id="dashboard" aria-labelledby="dashboard-title">
                <header class="welcome-header"><div><p class="overline">WEDNESDAY, JUNE 03, 2026</p><h1 id="dashboard-title">Production dashboard <span aria-hidden="true">☀</span></h1><p class="welcome-subtitle">A quick look at the work moving through your production desk.</p></div><div class="work-status"><span></span> Ready to make</div></header>
                <section class="production-stats" aria-label="Production overview">
                    <article class="stat-card stat-peach"><span>Incoming from Layout</span><strong>3</strong><small>Ready to get started</small></article>
                    <article class="stat-card stat-lilac"><span>Being made</span><strong>5</strong><small>Currently in progress</small></article>
                    <article class="stat-card stat-sage"><span>Finished this week</span><strong>8</strong><small>Recorded as complete</small></article>
                </section>
                <section class="card-surface dashboard-panel">
                    <div class="section-title"><div><p class="eyebrow">YOUR WORKSPACE</p><h2>Today's overview</h2><p>Pick up an incoming job or check finished pieces.</p></div></div>
                    <div class="dashboard-task-row"><span class="mini-task-icon">✂</span><span><strong>Next up: Everyday Tee</strong><small>#TF-2084 · 24 pieces · Due June 04</small></span><a class="dashboard-link" href="#incoming">Open incoming work →</a></div>
                    <div class="dashboard-task-row"><span class="mini-task-icon icon-sage">✓</span><span><strong>Recently finished: Weekend Tote</strong><small>#TF-2081 · Production complete</small></span><a class="dashboard-link" href="#finished">See finished work →</a></div>
                    <div class="dashboard-note"><span>✿</span><p><strong>Production reminder</strong><br>Check the layout notes before beginning a new batch.</p></div>
                </section>
            </section>

            <section class="screen-section" id="incoming" aria-label="Incoming production work">
            <header class="welcome-header">
                <div>
                    <p class="overline">WEDNESDAY, JUNE 03, 2026</p>
                    <h1>Good morning, Morgan <span aria-hidden="true">☀</span></h1>
                    <p class="welcome-subtitle">Layout's finished designs are ready for your production table.</p>
                </div>
                <div class="work-status"><span></span> Ready to make</div>
            </header>

            <section class="production-stats" aria-label="Production overview">
                <article class="stat-card stat-peach"><span>New from Layout</span><strong>3</strong><small>Ready to get started</small></article>
                <article class="stat-card stat-lilac"><span>Being made</span><strong>5</strong><small>Currently in progress</small></article>
                <article class="stat-card stat-sage"><span>Sent to inspection</span><strong>8</strong><small>Waiting for a quality check</small></article>
            </section>

            <div class="production-content">
                <section class="job-list card-surface" id="incoming-jobs" aria-labelledby="incoming-title">
                    <div class="section-title">
                        <div>
                            <p class="eyebrow">LAYOUT → PRODUCTION</p>
                            <h2 id="incoming-title">Incoming jobs</h2>
                            <p>Designs passed on by the Layout team.</p>
                        </div>
                        <span class="jobs-count">3 jobs</span>
                    </div>

                    <a class="job-row selected-job" href="#job-details">
                        <span class="job-swatch tee-swatch">TEE</span>
                        <span class="job-description"><strong>Everyday Tee</strong><small>#TF-2084 · 24 pieces</small><span class="job-meta"><i class="pill pill-new">New</i> Due Jun 04</span></span>
                        <span class="row-arrow">›</span>
                    </a>
                    <a class="job-row" href="#job-details">
                        <span class="job-swatch tote-swatch">TOTE</span>
                        <span class="job-description"><strong>Weekend Tote</strong><small>#TF-2081 · 12 pieces</small><span class="job-meta"><i class="pill pill-layout">Layout approved</i> Due Jun 05</span></span>
                        <span class="row-arrow">›</span>
                    </a>
                    <a class="job-row" href="#job-details">
                        <span class="job-swatch knit-swatch">KNIT</span>
                        <span class="job-description"><strong>Cozy Knit Cardigan</strong><small>#TF-2079 · 8 pieces</small><span class="job-meta"><i class="pill pill-new">New</i> Due Jun 06</span></span>
                        <span class="row-arrow">›</span>
                    </a>
                </section>

                <section class="job-detail card-surface" id="job-details" aria-labelledby="detail-title">
                    <div class="detail-title">
                        <div>
                            <p class="eyebrow">SELECTED JOB · #TF-2084</p>
                            <h2 id="detail-title">Everyday Tee</h2>
                        </div>
                        <span class="pill pill-layout">Layout approved</span>
                    </div>

                    <div class="design-board" role="img" aria-label="Layout preview of a natural colored T-shirt with a small centered front print">
                        <div class="board-circle"></div>
                        <div class="shirt"><span>little<br>things</span></div>
                        <span class="board-caption">Front print · centered</span>
                    </div>

                    <div class="job-information">
                        <div><span>Passed by</span><strong>Sky Kim <small>· Layout</small></strong></div>
                        <div><span>Quantity</span><strong>24 pieces</strong></div>
                        <div><span>Colors</span><strong>Natural, dusty rose</strong></div>
                        <div><span>Target date</span><strong>June 04, 2026</strong></div>
                    </div>

                    <div class="layout-message">
                        <span aria-hidden="true">“</span>
                        <div><strong>Note from Layout</strong><p>Use the approved spring lettering. Keep the small print centered on the front.</p></div>
                    </div>

                    <div class="production-checks">
                        <p class="check-title">PRODUCTION CHECK-IN</p>
                        <label><input type="checkbox"> Materials are ready</label>
                        <label><input type="checkbox"> Print placement checked</label>
                        <label><input type="checkbox"> Pieces are ready for inspection</label>
                    </div>

                    <form class="handoff-card" id="handoff" action="#" method="get">
                        <div class="handoff-title">
                            <div><p class="eyebrow">PASS IT ALONG</p><h3>Send this job to a team</h3></div>
                            <span aria-hidden="true">↗</span>
                        </div>
                        <label for="next-team" class="form-label">Next department</label>
                        <select class="form-select" id="next-team" name="next_department" required>
                            <option value="" selected disabled>Choose a department</option>
                            <option value="quality-inspection">Quality Inspection</option>
                            <option value="layout">Layout</option>
                        </select>
                        <label for="team-note" class="form-label note-label">Message for the next team <span>(optional)</span></label>
                        <textarea class="form-control" id="team-note" name="message" rows="2" placeholder="Add a quick update about this job..."></textarea>
                        <button class="btn send-button w-100" type="submit">Send job along <span aria-hidden="true">→</span></button>
                        <p class="form-footnote">The chosen department can review the job details and your note.</p>
                    </form>
                </section>
            </div>
            </section>

            <section class="screen-section" id="finished" aria-labelledby="finished-title">
                <header class="welcome-header"><div><p class="overline">PRODUCTION DESK · RECENT COMPLETIONS</p><h1 id="finished-title">Finished work</h1><p class="welcome-subtitle">Completed batches and pieces from your production table.</p></div><div class="work-status"><span></span> 8 completed this week</div></header>
                <section class="card-surface finished-panel">
                    <div class="section-title"><div><p class="eyebrow">COMPLETED BATCHES</p><h2>Ready and recorded</h2><p>A little history of finished production jobs.</p></div><span class="jobs-count">8 total</span></div>
                    <div class="finished-item"><span class="job-swatch tote-swatch">TOTE</span><span class="finished-copy"><strong>Weekend Tote</strong><small>#TF-2081 · 12 pieces completed</small><span>Completed Jun 03 · Quality check requested</span></span><span class="finished-badge">Complete</span></div>
                    <div class="finished-item"><span class="job-swatch tee-swatch">TEE</span><span class="finished-copy"><strong>Everyday Tee</strong><small>#TF-2075 · 18 pieces completed</small><span>Completed Jun 02 · Batch recorded</span></span><span class="finished-badge">Complete</span></div>
                    <div class="finished-item"><span class="job-swatch knit-swatch">KNIT</span><span class="finished-copy"><strong>Cozy Knit Cardigan</strong><small>#TF-2071 · 8 pieces completed</small><span>Completed Jun 01 · Batch recorded</span></span><span class="finished-badge">Complete</span></div>
                    <div class="finished-footer"><span aria-hidden="true">✿</span> Nice work! Your completed batches are all recorded here.</div>
                </section>
            </section>

            <footer class="page-footer">Made by hand, with care <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
