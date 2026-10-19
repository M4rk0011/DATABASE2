<?php if (!($isLoading)): ?><div class="concern-details-container">
  <?php if ($concern): ?><div>
    <div class="page-header">
      <button class="btn btn-outline" data-href="<?= e(url('concerns')) ?>">Back to Concerns</button>
      <div class="header-actions">
        <?php if (canEditConcernRecord()): ?><button class="btn btn-primary" data-href="<?= e(url('report', ['id' => $concern->id])) ?>">Edit</button><?php endif; ?>
        <?php if ($isAdmin): ?><button class="btn btn-info" data-print="true">
          🖨️ Print
        </button><?php endif; ?>
        <?php if ($isAdmin): ?><button class="btn btn-success" data-href="export.php?id=<?= e($concern->id) ?>">
          📄 PDF
        </button><?php endif; ?>
        <?php if ($isAdmin): ?><button class="btn btn-warning" data-share="true">
          📧 Share
        </button><?php endif; ?>
        <?php if (canDeleteConcern()): ?><button class="btn btn-danger" data-action="delete-report" data-id="<?= e($concern->id) ?>">Delete</button><?php endif; ?>
      </div>
    </div>

    <div class="concern-content">
      <div class="main-section">
        <div class="concern-header">
          <h1><?= e($concern->title) ?></h1>
          <div class="concern-badges">
            <span class="severity-badge" style="background-color:<?= e(getSeverityColor($concern->severity)) ?>">
              <?= e(strtoupper($concern->severity)) ?>
            </span>
            <span class="status-badge" style="background-color:<?= e(getStatusColor($concern->status)) ?>">
              <?= e(strtoupper(str_replace('_', ' ', $concern->status))) ?>
            </span>
          </div>
        </div>

        <div class="concern-description">
          <h3>Description</h3>
          <p><?= e($concern->description) ?></p>
        </div>

        <div class="concern-info">
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Category:</span>
              <span class="value"><?= e(strtoupper(str_replace('_', ' ', $concern->category))) ?></span>
            </div>
            <div class="info-item">
              <span class="label">Severity:</span>
              <span class="value severity" style="color:<?= e(getSeverityColor($concern->severity)) ?>">
                <?= e(strtoupper($concern->severity)) ?>
              </span>
            </div>
            <div class="info-item">
              <span class="label">Status:</span>
              <span class="value status" style="color:<?= e(getStatusColor($concern->status)) ?>">
                <?= e(strtoupper(str_replace('_', ' ', $concern->status))) ?>
              </span>
            </div>
            <div class="info-item">
              <span class="label">Reported:</span>
              <span class="value"><?= e(fmtdate($concern->reportedAt)) ?></span>
            </div>
            <div class="info-item">
              <span class="label">Updated:</span>
              <span class="value"><?= e(fmtdate($concern->updatedAt)) ?></span>
            </div>
            <?php if ($concern->resolvedAt): ?><div class="info-item">
              <span class="label">Resolved:</span>
              <span class="value"><?= e(fmtdate($concern->resolvedAt)) ?></span>
            </div><?php endif; ?>
          </div>
        </div>

        <div class="location-info">
          <h3>Location</h3>
          <div class="location-details">
            <p><strong>Address:</strong> <?= e($concern->location->address) ?></p>
            <?php if ($concern->location->building): ?><p><strong>Building:</strong> <?= e($concern->location->building) ?></p><?php endif; ?>
            <?php if ($concern->location->floor): ?><p><strong>Floor:</strong> <?= e($concern->location->floor) ?></p><?php endif; ?>
            <?php if ($concern->location->room): ?><p><strong>Room:</strong> <?= e($concern->location->room) ?></p><?php endif; ?>
            <?php if ($concern->location->landmark): ?><p><strong>Landmark:</strong> <?= e($concern->location->landmark) ?></p><?php endif; ?>
            <p><strong>Coordinates:</strong> <?= e(number_format($concern->location->latitude, 6)) ?>, <?= e(number_format($concern->location->longitude, 6)) ?></p>
          </div>
        </div>

        <?php if ((!($concern->isAnonymous) || $isAdmin)): ?><div class="contact-info">
          <h3>Contact Information</h3>
          <div class="contact-details">
            <p><strong>Name:</strong> <?= e(($concern->isAnonymous ? 'Anonymous Reporter' : $concern->contactInfo->name)) ?></p>
            <p><strong>Email:</strong> <?= e($concern->contactInfo->email) ?></p>
            <?php if ($concern->contactInfo->phone): ?><p><strong>Phone:</strong> <?= e($concern->contactInfo->phone) ?></p><?php endif; ?>
            <?php if ($concern->contactInfo->studentId): ?><p><strong>Student ID:</strong> <?= e($concern->contactInfo->studentId) ?></p><?php endif; ?>
            <?php if ($concern->contactInfo->department): ?><p><strong>Department:</strong> <?= e($concern->contactInfo->department) ?></p><?php endif; ?>
          </div>
        </div><?php endif; ?>

        <?php if ((count($concern->witnesses) > 0)): ?><div class="witnesses-info">
          <h3>Witnesses</h3>
          <ul>
            <?php foreach ($concern->witnesses as $witness): ?><li><?= e($witness) ?></li><?php endforeach; ?>
          </ul>
        </div><?php endif; ?>

        <?php if ($concern->additionalNotes): ?><div class="additional-notes">
          <h3>Additional Notes</h3>
          <p><?= e($concern->additionalNotes) ?></p>
        </div><?php endif; ?>
      </div>

      <div class="sidebar">
        <?php if (canUpdateStatus()): ?><div class="status-actions">
          <h3>Status Actions</h3>
          <div class="action-buttons">
            <?php if (($concern->status === 'reported')): ?><button class="btn btn-primary" data-action="status" data-id="<?= e($concern->id) ?>" data-value="<?= e('investigating') ?>">
              Start Investigation
            </button><?php endif; ?>
            <?php if (($concern->status === 'investigating')): ?><button class="btn btn-success" data-action="status" data-id="<?= e($concern->id) ?>" data-value="<?= e('resolved') ?>">
              Mark Resolved
            </button><?php endif; ?>
            <?php if (($concern->status === 'investigating')): ?><button class="btn btn-warning" data-action="status" data-id="<?= e($concern->id) ?>" data-value="<?= e('false_alarm') ?>">
              Mark as False Alarm
            </button><?php endif; ?>
            <?php if (($concern->status === 'resolved')): ?><button class="btn btn-secondary" data-action="status" data-id="<?= e($concern->id) ?>" data-value="<?= e('closed') ?>">
              Close Case
            </button><?php endif; ?>
          </div>
        </div><?php endif; ?>

        <div class="concern-summary">
          <h3>Concern Summary</h3>
          <div class="summary-stats">
            <div class="stat-item">
              <span class="stat-label">Concern ID:</span>
              <span class="stat-value">#<?= e($concern->id) ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Reporter:</span>
              <span class="stat-value"><?= e(($concern->isAnonymous ? 'Anonymous' : $concern->contactInfo->name)) ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Assigned To:</span>
              <span class="stat-value"><?= e(($concern->assignedTo ?: 'Unassigned')) ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Time Elapsed:</span>
              <span class="stat-value"><?= e(getTimeElapsed($concern->reportedAt)) ?></span>
            </div>
          </div>
        </div>

        <div class="quick-actions">
          <h3>Quick Actions</h3>
          <div class="action-buttons">
            <button class="btn btn-outline-primary" data-print="true">Print Report</button>
            <button class="btn btn-outline-primary" data-href="export.php?id=<?= e($concern->id) ?>">Export PDF</button>
            <button class="btn btn-outline-primary" data-share="true">Share Link</button>
          </div>
        </div>
      </div>
    </div>
  </div><?php endif; ?>
</div><?php endif; ?>



<?php if (!$concern): ?>
  <div class="not-found">
    <p>Concern not found.</p>
    <button class="btn btn-primary" data-href="<?= e(url('concerns')) ?>">Back to Concerns</button>
  </div>
<?php endif; ?>
