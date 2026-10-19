<div class="concern-list-container">
  <div class="page-header">
    <h2>All Concerns</h2>
    <a href="<?= e(url('report')) ?>" class="btn btn-primary">Report New Concern</a>
  </div>

  
  <div class="filters-section">
    <div class="filter-row">
      <div class="filter-group">
        <label for="search">Search:</label>
        <input type="text" id="search" value="<?= e($searchTerm) ?>" name="search" data-filter="true" placeholder="Search concerns...">
      </div>
      
      <div class="filter-group">
        <label for="status">Status:</label>
        <select id="status" name="statusFilter" data-filter="true">
          <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
          <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
            <?= e(strtoupper(str_replace('_', ' ', $status))) ?>
          </option><?php endforeach; ?>
        </select>
      </div>
      
      <div class="filter-group">
        <label for="severity">Severity:</label>
        <select id="severity" name="severityFilter" data-filter="true">
          <option value="" <?= $severityFilter === '' ? 'selected' : '' ?>>All Severities</option>
          <?php foreach ($severities as $severity): ?><option value="<?= e($severity) ?>" <?= $severityFilter === $severity ? 'selected' : '' ?>>
            <?= e(strtoupper($severity)) ?>
          </option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  
  <?php if ($isLoading): ?><div class="loading-state">
    <p>Loading concerns...</p>
  </div><?php endif; ?>

  
  
    <?php if ((!($isLoading) && (count($concerns) > 0))): ?><div class="concerns-grid">
      <?php foreach ($concerns as $concern): ?><div class="concern-card">
        <div class="card-header">
          <h3><?= e($concern->title) ?></h3>
          <div class="concern-badges">
            <span class="severity-badge" style="background-color:<?= e(getSeverityColor($concern->severity)) ?>">
              <?= e(strtoupper($concern->severity)) ?>
            </span>
            <span class="status-badge" style="background-color:<?= e(getStatusColor($concern->status)) ?>">
              <?= e(strtoupper(str_replace('_', ' ', $concern->status))) ?>
            </span>
          </div>
        </div>
        
        <div class="card-content">
          <p class="concern-description"><?= e($concern->description) ?></p>
          
          <div class="concern-details">
            <div class="detail-item">
              <span class="label">Category:</span>
              <span class="value"><?= e(strtoupper(str_replace('_', ' ', $concern->category))) ?></span>
            </div>
            <div class="detail-item">
              <span class="label">Location:</span>
              <span class="value"><?= e($concern->location->address) ?></span>
            </div>
            <div class="detail-item">
              <span class="label">Reported:</span>
              <span class="value"><?= e(fmtdate($concern->reportedAt)) ?></span>
            </div>
            <div class="detail-item">
              <span class="label">Reporter:</span>
              <span class="value"><?= e(($concern->isAnonymous ? 'Anonymous' : $concern->contactInfo->name)) ?></span>
            </div>
          </div>
        </div>
        
        <div class="card-actions">
          <a href="<?= e(url('details', ['id' => $concern->id])) ?>" class="btn btn-outline-primary">View Details</a>
          <?php if (canEditConcernContent($concern)): ?><button type="button" class="btn btn-primary" data-href="<?= e(url('report', ['id' => $concern->id])) ?>">
            Edit
          </button><?php endif; ?>

          <?php if (canUpdateStatus()): ?><div class="status-actions">
            <select class="status-select" value="<?= e($concern->status) ?>" data-status-change="true" data-id="<?= e($concern->id) ?>">
              <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $concern->status === $status ? 'selected' : '' ?>>
                <?= e(strtoupper(str_replace('_', ' ', $status))) ?>
              </option><?php endforeach; ?>
            </select>
          </div><?php endif; ?>
          
          <?php if (canDeleteConcern()): ?><button class="btn btn-danger" data-action="delete-report" data-id="<?= e($concern->id) ?>">
            Delete
          </button><?php endif; ?>
        </div>
      </div><?php endforeach; ?>
    </div><?php endif; ?>

    
    <?php if ((!($isLoading) && (count($concerns) === 0))): ?><div class="empty-state">
      <p>No concerns found.</p>
      <a href="<?= e(url('report')) ?>" class="btn btn-primary">Report First Concern</a>
    </div><?php endif; ?>
  
</div>