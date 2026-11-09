<?php
session_start();

require "../logout.php";

if($_SESSION["role"] != "layout"){
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8f5ef">
    <title>Layout Studio | Thread &amp; Form</title>
    <link rel="stylesheet" href="../bootstrap_template/css/bootstrap.min.css">
    <link rel="stylesheet" href="layout_artist.css">
</head>
<body>
    <div class="studio-shell">
        <aside class="studio-sidebar">
            <a class="studio-brand" href="#dashboard">
                <span class="brand-mark">t.</span>
                <span>thread &amp; form<small>LAYOUT STUDIO</small></span>
            </a>

            <p class="menu-label">WORKSPACE</p>
            <nav class="studio-nav" aria-label="Layout workspace">
                <a class="studio-nav-link selected" href="#dashboard" aria-current="page"><span aria-hidden="true">⌂</span> Dashboard</a>
                <a class="studio-nav-link" href="#incoming"><span aria-hidden="true">▤</span> Incoming work <span class="queue-count">4</span></a>
                <a class="studio-nav-link" href="#finished"><span aria-hidden="true">✓</span> Finished work</a>
                <form method="POST">
                    <button class="btn btn-danger" name="logout">Logout</button>
                </form>
            </nav>

            <div class="artist-profile">
                <span class="artist-avatar">SK</span>
                <span><strong>Sky Kim</strong><small>Layout artist</small></span>
                <span class="profile-menu" aria-hidden="true">···</span>
            </div>
        </aside>


        <main class="studio-main">
            <section class="screen-section" id="dashboard" aria-labelledby="dashboard-title">
                <header class="studio-header">
                    <div>
                        <h1 id="dashboard-title">Layout Artists</h1>
                    </div>
                    <div class="artist-status">Layout Artists Studio</div>
                </header>
                <section class="summary-row" aria-label="Layout work summary">
                    <article class="summary-card summary-pink"><span>Incoming work</span><strong>4</strong><small>Ready for a layout review</small></article>
                    <article class="summary-card summary-lilac"><span>In progress</span><strong>2</strong><small>On your design table</small></article>
                    <article class="summary-card summary-green"><span>Finished this week</span><strong>8</strong><small>Ready for the next step</small></article>
                </section>
                <section class="panel dashboard-panel">
                    <div class="section-heading"><div><p class="section-kicker">YOUR WORKSPACE</p><h2>Today's overview</h2><p>Keep an eye on the jobs moving through your desk.</p></div></div>
                    <div class="dashboard-task-row"><span class="mini-task-icon">✎</span><span><strong>Next up: Everyday Tee</strong><small>Order #TF-2084 · Layout due June 04</small></span><a class="dashboard-link" href="#incoming">Open incoming work →</a></div>
                    <div class="dashboard-task-row"><span class="mini-task-icon icon-sage">✓</span><span><strong>Recently finished: Weekend Tote</strong><small>Order #TF-2078 · Passed to the next step</small></span><a class="dashboard-link" href="#finished">See finished work →</a></div>
                    <div class="dashboard-note"><span>✿</span><p><strong>Little reminder</strong><br>Check the artwork notes before sending a design along.</p></div>
                </section>
            </section>

            <section class="screen-section" id="incoming" aria-label="Incoming layout work">
            <header class="studio-header">
                <div>
                    <p class="date-label">TUESDAY, JUNE 02, 2026</p>
                    <h1>Hi, Sky <span aria-hidden="true">✳</span></h1>
                    <p class="header-subtitle">Here are the order details Admin passed your way.</p>
                </div>
                <div class="artist-status"><span></span> Studio is open</div>
            </header>

            <section class="summary-row" aria-label="Work queue summary">
                <article class="summary-card summary-pink"><span>Waiting for layout</span><strong>4</strong><small>Ready for your review</small></article>
                <article class="summary-card summary-lilac"><span>In progress</span><strong>2</strong><small>On your design table</small></article>
                <article class="summary-card summary-green"><span>Sent to next team</span><strong>8</strong><small>Handed off this week</small></article>
            </section>

            <div class="studio-grid">
                <section class="work-queue panel" id="work-queue" aria-labelledby="queue-title">
                    <div class="section-heading">
                        <div>
                            <p class="section-kicker">FROM ADMIN</p>
                            <h2 id="queue-title">Incoming work</h2>
                            <p>Orders checked and ready for layout.</p>
                        </div>
                        <span class="queue-total">4 tasks</span>
                    </div>

                    <a class="work-card active-work" href="#selected-task" aria-current="true">
                        <span class="work-thumbnail tee-thumbnail"><span>TEE</span></span>
                        <span class="work-info">
                            <span class="work-title">Everyday Tee</span>
                            <span class="work-meta">Order #TF-2084 · 2 color options</span>
                            <span class="work-tags"><span class="tag-new">New</span><span>Due Jun 04</span></span>
                        </span>
                        <span class="work-chevron" aria-hidden="true">›</span>
                    </a>

                    <a class="work-card" href="#selected-task">
                        <span class="work-thumbnail tote-thumbnail"><span>TOTE</span></span>
                        <span class="work-info">
                            <span class="work-title">Weekend Tote</span>
                            <span class="work-meta">Order #TF-2081 · Print placement</span>
                            <span class="work-tags"><span class="tag-progress">In progress</span><span>Due Jun 05</span></span>
                        </span>
                        <span class="work-chevron" aria-hidden="true">›</span>
                    </a>

                    <a class="work-card" href="#selected-task">
                        <span class="work-thumbnail hoodie-thumbnail"><span>KNIT</span></span>
                        <span class="work-info">
                            <span class="work-title">Cozy Knit Cardigan</span>
                            <span class="work-meta">Order #TF-2079 · Label artwork</span>
                            <span class="work-tags"><span class="tag-new">New</span><span>Due Jun 06</span></span>
                        </span>
                        <span class="work-chevron" aria-hidden="true">›</span>
                    </a>

                    <a class="work-card" href="#selected-task">
                        <span class="work-thumbnail cap-thumbnail"><span>CAP</span></span>
                        <span class="work-info">
                            <span class="work-title">Weekend Cap</span>
                            <span class="work-meta">Order #TF-2076 · Embroidery mockup</span>
                            <span class="work-tags"><span class="tag-new">New</span><span>Due Jun 07</span></span>
                        </span>
                        <span class="work-chevron" aria-hidden="true">›</span>
                    </a>
                </section>

                <section class="task-detail panel" id="selected-task" aria-labelledby="task-title">
                    <div class="detail-heading">
                        <div>
                            <p class="section-kicker">SELECTED TASK · #TF-2084</p>
                            <h2 id="task-title">Everyday Tee</h2>
                        </div>
                        <span class="tag-new">New</span>
                    </div>

                    <div class="design-preview">
                        <div class="preview-sun"></div>
                        <div class="shirt-shape"><span>little<br>things</span></div>
                        <span class="preview-note">front print · centered</span>
                    </div>

                    <div class="task-facts">
                        <div><span>Submitted by</span><strong>Jamie Lee <small>· Admin</small></strong></div>
                        <div><span>Order quantity</span><strong>24 pieces</strong></div>
                        <div><span>Color options</span><strong>Natural, dusty rose</strong></div>
                        <div><span>Requested by</span><strong>June 04, 2026</strong></div>
                    </div>

                    <div class="admin-note">
                        <span aria-hidden="true">“</span>
                        <div><strong>Admin's order notes</strong><p>Please use the new spring collection lettering. Customer asked for a small print on the front.</p></div>
                    </div>

                    <div class="layout-checklist">
                        <p class="checklist-title">LAYOUT CHECKLIST</p>
                        <label><input type="checkbox"> Confirm artwork size and placement</label>
                        <label><input type="checkbox"> Prepare color variations</label>
                        <label><input type="checkbox"> Add final layout notes</label>
                    </div>

                    <form class="handoff-form" id="handoff" action="#" method="get">
                        <div class="handoff-heading">
                            <div>
                                <p class="section-kicker">NEXT STEP</p>
                                <h3>Send to another department</h3>
                            </div>
                            <span aria-hidden="true">↗</span>
                        </div>
                        <label for="department" class="form-label">Choose a department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="" selected disabled>Select where this goes next</option>
                            <option value="production">Production</option>
                            <option value="quality-assurance">Quality Assurance</option>
                            <option value="admin">Admin</option>
                        </select>
                        <label for="handoff-note" class="form-label handoff-note-label">Note for the next team <span>(optional)</span></label>
                        <textarea class="form-control" id="handoff-note" name="handoff_note" rows="2" placeholder="Anything they should know about this layout?"></textarea>
                        <button class="btn send-button w-100" type="submit">Send work along <span aria-hidden="true">→</span></button>
                        <p class="handoff-footnote">The selected team can use your note when they pick up this order.</p>
                    </form>
                </section>
            </div>
            </section>

            <section class="screen-section" id="finished" aria-labelledby="finished-title">
                <header class="studio-header">
                    <div><p class="date-label">LAYOUT STUDIO · RECENT COMPLETIONS</p><h1 id="finished-title">Finished work</h1><p class="header-subtitle">Layouts you've completed and passed along.</p></div>
                    <div class="artist-status"><span></span> 8 completed this week</div>
                </header>
                <section class="panel finished-panel">
                    <div class="section-heading"><div><p class="section-kicker">YOUR COMPLETED DESIGNS</p><h2>Ready and sent</h2><p>A record of finished layout tasks.</p></div><span class="queue-total">8 total</span></div>
                    <div class="finished-item"><span class="work-thumbnail tote-thumbnail"><span>TOTE</span></span><span class="finished-copy"><strong>Weekend Tote</strong><small>#TF-2078 · Print artwork approved</small><span>Completed Jun 02 · Passed to the next step</span></span><span class="finished-badge">Finished</span></div>
                    <div class="finished-item"><span class="work-thumbnail hoodie-thumbnail"><span>KNIT</span></span><span class="finished-copy"><strong>Cozy Knit Cardigan</strong><small>#TF-2074 · Label layout prepared</small><span>Completed Jun 01 · Passed to the next step</span></span><span class="finished-badge">Finished</span></div>
                    <div class="finished-item"><span class="work-thumbnail cap-thumbnail"><span>CAP</span></span><span class="finished-copy"><strong>Weekend Cap</strong><small>#TF-2072 · Embroidery mockup approved</small><span>Completed May 31 · Passed to the next step</span></span><span class="finished-badge">Finished</span></div>
                    <div class="finished-footer"><span aria-hidden="true">✿</span> Nice work! Your latest layouts are all recorded here.</div>
                </section>
            </section>

            <footer class="studio-footer">Made with care in the layout studio <span aria-hidden="true">♡</span></footer>
        </main>
    </div>
</body>
</html>
