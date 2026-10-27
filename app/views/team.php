<section class="crew-section" aria-labelledby="crew-title">
  <div class="crew-heading"><span class="eyebrow">MEET THE BUILDERS</span><h2 id="crew-title">Step into our studio.</h2><p>Walk around, meet Justice Little League, and discover who built the website.</p></div>
  <div class="crew-game" id="crew-game">
    <canvas id="crew-canvas" tabindex="0" aria-label="Walkable 3D team studio" aria-describedby="crew-help"></canvas>
    <div class="crew-labels" id="crew-labels" aria-hidden="true"></div>
    <div class="crew-overlay" id="crew-overlay"><span class="crew-kicker">JUSTICE LITTLE LEAGUE</span><h3>Your team. In 3D.</h3><p>Explore a small studio with six stylized characters.<br>Walk up to someone to read their contribution.</p><button type="button" id="crew-start">Enter the studio →</button><small>Stylized avatars, not photographs of the members.</small></div>
    <div class="crew-hud" id="crew-hud" hidden><span id="crew-count">0 / 6 met</span><button type="button" id="crew-reset">Reset position</button><button type="button" id="crew-exit">Pause / Exit</button></div>
    <div class="crew-crosshair" id="crew-crosshair" hidden aria-hidden="true">+</div>
    <div class="crew-info" id="crew-info" role="status" hidden></div>
    <div class="crew-controls" id="crew-controls" hidden aria-label="Movement controls"><button type="button" data-crew-key="arrowleft" aria-label="Turn left">↶</button><button type="button" data-crew-key="w" aria-label="Walk forward">↑</button><button type="button" data-crew-key="arrowright" aria-label="Turn right">↷</button><button type="button" data-crew-key="a" aria-label="Step left">←</button><button type="button" data-crew-key="s" aria-label="Walk backward">↓</button><button type="button" data-crew-key="d" aria-label="Step right">→</button></div>
  </div>
  <p id="crew-help" class="crew-help">WASD to walk · Left/right arrows to turn · Drag the scene to look · Escape to pause. On mobile, hold the movement buttons and drag to look.</p>
  <details class="crew-roster"><summary>Read the team credits</summary><ul>
  <?php foreach([
    ['Mark Lloyd Yadao','Frontend & backend developer'],
    ['Bryan Dave Balboa','Website co-developer & design'],
    ['Ace Danioan','Page drafts & report assistance'],
    ['Dhaff Kindipan','Dashboard & navigation assistance'],
    ['Derek Vinoya','Map drafts & interface assistance'],
    ['Sean Ballug','Initial planning & suggestions'],
  ] as $member): ?><li><strong><?= e($member[0]) ?></strong> — <?= e($member[1]) ?></li><?php endforeach; ?>
  </ul></details>
</section>
