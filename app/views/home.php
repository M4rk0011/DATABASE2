<div class="dashboard">
  <h2>Art Commission Dashboard</h2>
  <p class="subtitle">Manage your freelance art business efficiently</p>
  
  
  <div class="stats-grid">
    <div class="stat-card">
      <h3>Total Commissions</h3>
      <div class="stat-number"><?= e($stats->total) ?></div>
      <div class="stat-label">Active Projects</div>
    </div>
    
    <div class="stat-card">
      <h3>In Progress</h3>
      <div class="stat-number"><?= e($stats->inProgress) ?></div>
      <div class="stat-label">Currently Working</div>
    </div>
    
    <div class="stat-card">
      <h3>Completed</h3>
      <div class="stat-number"><?= e($stats->completed) ?></div>
      <div class="stat-label">Finished Projects</div>
    </div>
    
    <div class="stat-card">
      <h3>Total Revenue</h3>
      <div class="stat-number">$<?= e($stats->totalRevenue) ?></div>
      <div class="stat-label">All Time Earnings</div>
    </div>
  </div>
  
  
  <div class="recent-section">
    <h3>Recent Commissions</h3>
    <?php if ((count($recentCommissions) > 0)): ?><div class="commission-list">
      <?php foreach ($recentCommissions as $commission): ?><div class="commission-item">
        <div class="commission-header">
          <h4><?= e($commission->title) ?></h4>
          <span class="status <?= e(('status-' . $commission->status)) ?>">
            <?= e(strtoupper(str_replace('_', ' ', $commission->status))) ?>
          </span>
        </div>
        <div class="commission-details">
          <p><strong>Client:</strong> <?= e($commission->clientName) ?></p>
          <p><strong>Price:</strong> $<?= e($commission->price) ?></p>
          <p><strong>Progress:</strong> <?= e($commission->progress) ?>%</p>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= e($commission->progress) ?>%"></div>
          </div>
        </div>
      </div><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if ((count($recentCommissions) === 0)): ?><div>
      <p>No commissions yet. Start by creating your first commission!</p>
    </div><?php endif; ?>
  </div>
  
  
  <div class="sdg-section">
    <h3>SDG 8 Impact</h3>
    <div class="sdg-content">
      <p>This system supports <strong>SDG 8 - Decent Work and Economic Growth</strong> by:</p>
      <ul>
        <li>Enabling sustainable income opportunities for freelance artists</li>
        <li>Promoting productivity and efficient workflow management</li>
        <li>Supporting entrepreneurship in the creative economy</li>
        <li>Facilitating fair client relationships and payment tracking</li>
      </ul>
      <div class="impact-metrics">
        <div class="metric">
          <span class="metric-value"><?= e($stats->total) ?></span>
          <span class="metric-label">Projects Managed</span>
        </div>
        <div class="metric">
          <span class="metric-value">$<?= e($stats->totalRevenue) ?></span>
          <span class="metric-label">Economic Impact</span>
        </div>
      </div>
    </div>
  </div>
</div>
