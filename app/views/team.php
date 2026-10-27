<section class="team-section" aria-labelledby="team-title">
  <div class="team-heading"><span class="eyebrow">THE PEOPLE BEHIND THE PROJECT</span><h2 id="team-title">Justice Little League</h2><p>Built together for a more connected campus.</p></div>
  <input class="team-motion-input" type="checkbox" id="team-pause">
  <label class="team-motion-label" for="team-pause">Pause card animation</label>
  <div class="team-grid">
    <?php foreach([
      ['MY','Mark Lloyd Yadao','Frontend & backend developer','Developer'],
      ['BB','Bryan Dave Balboa','Website co-developer & design','Developer'],
      ['AD','Ace Danioan','Page drafts & report assistance','Support'],
      ['DK','Dhaff Kindipan','Dashboard & navigation assistance','Support'],
      ['DV','Derek Vinoya','Map drafts & interface assistance','Support'],
      ['SB','Sean Ballug','Initial planning & suggestions','Planning'],
    ] as $i=>$member): ?>
    <article class="team-card" style="--card-order:<?= $i ?>">
      <div class="team-card-inner"><span class="team-avatar" aria-hidden="true"><?= e($member[0]) ?></span><span class="team-badge"><?= e($member[3]) ?></span><h3><?= e($member[1]) ?></h3><p><?= e($member[2]) ?></p><span class="team-card-rule" aria-hidden="true"></span></div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
