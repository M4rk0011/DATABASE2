<div class="analytics-page">
  <div class="page-header">
    <h2>Analytics Dashboard</h2>
    <p>Track your art commission business performance and insights</p>
  </div>

  
  <div class="metrics-grid">
    <div class="metric-card">
      <h3>Total Revenue</h3>
      <div class="metric-value">$<?= e($stats->totalRevenue) ?></div>
      <div class="metric-change positive">+12% from last month</div>
    </div>
    
    <div class="metric-card">
      <h3>Active Commissions</h3>
      <div class="metric-value"><?= e($stats->inProgress) ?></div>
      <div class="metric-change neutral">Same as last week</div>
    </div>
    
    <div class="metric-card">
      <h3>Completion Rate</h3>
      <div class="metric-value"><?= e(number_format((float)getCompletionRate(), 1)) ?>%</div>
      <div class="metric-change positive">+5% improvement</div>
    </div>
    
    <div class="metric-card">
      <h3>Average Commission Value</h3>
      <div class="metric-value">$<?= e(number_format((float)getAverageCommissionValue(), 1)) ?></div>
      <div class="metric-change positive">+$50 increase</div>
    </div>
  </div>

  
  <div class="charts-section">
    <div class="chart-container">
      <h3>Commission Status Distribution</h3>
      <div class="status-chart">
        <?php foreach (pairs($statusData) as $item): ?><div class="chart-item">
          <div class="chart-bar">
            <div class="bar-fill" style="width:<?= e((($item->value / ($stats->total * 100)))) ?>%"></div>
          </div>
          <div class="chart-label">
            <span class="label-text"><?= e($item->key) ?></span>
            <span class="label-value"><?= e($item->value) ?></span>
          </div>
        </div><?php endforeach; ?>
      </div>
    </div>

    <div class="chart-container">
      <h3>Artwork Type Distribution</h3>
      <div class="type-chart">
        <?php foreach (pairs($artworkTypeData) as $item): ?><div class="chart-item">
          <div class="chart-bar">
            <div class="bar-fill" style="width:<?= e((($item->value / ($stats->total * 100)))) ?>%"></div>
          </div>
          <div class="chart-label">
            <span class="label-text"><?= e($item->key) ?></span>
            <span class="label-value"><?= e($item->value) ?></span>
          </div>
        </div><?php endforeach; ?>
      </div>
    </div>
  </div>

  
  <div class="trends-section">
    <h3>Monthly Performance Trends</h3>
    <div class="trends-grid">
      <div class="trend-card">
        <h4>Revenue Trend</h4>
        <div class="trend-chart">
          <?php foreach ($monthlyData as $month): ?><div class="trend-item">
            <div class="trend-bar">
              <div class="trend-fill" style="height:<?= e((($month->revenue / (2000 * 100)))) ?>%"></div>
            </div>
            <span class="trend-label"><?= e($month->month) ?></span>
            <span class="trend-value">$<?= e($month->revenue) ?></span>
          </div><?php endforeach; ?>
        </div>
      </div>
      
      <div class="trend-card">
        <h4>Commission Volume</h4>
        <div class="trend-chart">
          <?php foreach ($monthlyData as $month): ?><div class="trend-item">
            <div class="trend-bar">
              <div class="trend-fill volume" style="height:<?= e((($month->commissions / (10 * 100)))) ?>%"></div>
            </div>
            <span class="trend-label"><?= e($month->month) ?></span>
            <span class="trend-value"><?= e($month->commissions) ?></span>
          </div><?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  
  <div class="insights-section">
    <h3>Performance Insights</h3>
    <div class="insights-grid">
      <div class="insight-card">
        <div class="insight-icon">trending_up</div>
        <h4>Revenue Growth</h4>
        <p>Your monthly revenue has increased by 23% compared to the previous quarter. Keep up the great work!</p>
      </div>
      
      <div class="insight-card">
        <div class="insight-icon">schedule</div>
        <h4>Completion Time</h4>
        <p>Average commission completion time is 2.3 weeks, which is 15% faster than your baseline.</p>
      </div>
      
      <div class="insight-card">
        <div class="insight-icon">star</div>
        <h4>Client Satisfaction</h4>
        <p>95% of clients have completed multiple commissions, indicating high satisfaction levels.</p>
      </div>
      
      <div class="insight-card">
        <div class="insight-icon">category</div>
        <h4>Popular Services</h4>
        <p>Digital Art and Portrait commissions are your most requested services, representing 60% of all work.</p>
      </div>
    </div>
  </div>
</div>
