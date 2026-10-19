<?php if ($currentUser): ?><div class="dashboard-container">
  <div class="dashboard-header">
    <div class="header-content">
      <div class="user-info">
        <span class="eyebrow">YOUR CAMPUS AT A GLANCE</span><h1>Welcome, <?= e(ucwords((string)$currentUser->profile->firstName)) ?>!</h1>
        <p><?= e((($currentUser->role === 'admin') ? 'Administrator' : (($currentUser->role === 'security') ? 'Security Officer' : 'Student'))) ?></p>
      </div>
      <div class="header-actions">
        <?php if ($currentUser): ?><button class="btn btn-primary" data-href="<?= e(url('report')) ?>">Report Concern</button><?php endif; ?>
        <button class="btn btn-outline" data-href="<?= e(url('map')) ?>">View Map</button>
        <?php if (isAdmin()): ?><button class="btn btn-outline" data-href="#user-management-section">User Management</button><?php endif; ?>
        
      </div>
    </div>
  </div>

  <div class="dashboard-content">
    
    <div class="stats-section">
      <h2>Overview</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M4 3h16v18H4z M7 7h10v2H7z M7 12h10v2H7z"/></svg>
          </div>
          <div class="stat-info">
            <h3><?= e($stats->totalConcerns) ?></h3>
            <p>Total Concerns</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m-1 4h2v6l4 3-1 2-5-4z"/></svg>
          </div>
          <div class="stat-info">
            <h3><?= e($stats->openConcerns) ?></h3>
            <p>Open Concerns</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m-2 14-4-4 2-2 2 2 6-6 2 2z"/></svg>
          </div>
          <div class="stat-info">
            <h3><?= e($stats->resolvedConcerns) ?></h3>
            <p>Resolved Concerns</p>
          </div>
        </div>

        <?php if (isAdmin()): ?><div class="stat-card">
          <div class="stat-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2 1 22h22z M11 8h2v7h-2z M11 17h2v2h-2z"/></svg>
          </div>
          <div class="stat-info">
            <h3><?= e($stats->criticalConcerns) ?></h3>
            <p>Critical Concerns</p>
          </div>
        </div><?php endif; ?>
      </div>
    </div>

    
    <?php if (isAdmin()): ?><div class="admin-stats">
      <h2>Administrative Overview</h2>
      <div class="admin-grid">
        <div class="admin-card">
          <h3>Concerns by Category</h3>
          <div class="category-stats">
            <?php foreach (pairs($stats->concernsByCategory) as $item): ?><div class="category-item">
              <span class="category-name"><?= e(getCategoryDisplayName($item->key)) ?></span>
              <span class="category-count"><?= e($item->value) ?></span>
            </div><?php endforeach; ?>
          </div>
        </div>

        <div class="admin-card">
          <h3>Concerns by Severity</h3>
          <div class="severity-stats">
            <?php foreach (pairs($stats->concernsBySeverity) as $item): ?><div class="severity-item">
              <span class="severity-dot" style="background-color:<?= e(getSeverityColorFromKey($item->key)) ?>"></span>
              <span class="severity-name"><?= e(getSeverityDisplayName($item->key)) ?></span>
              <span class="severity-count"><?= e($item->value) ?></span>
            </div><?php endforeach; ?>
          </div>
        </div>

        <div class="admin-card">
          <h3>Performance Metrics</h3>
          <div class="metrics">
            <div class="metric-item">
              <span class="metric-label">Avg Resolution Time:</span>
              <span class="metric-value"><?= e(number_format((float)$stats->averageResolutionTime, 1)) ?> hours</span>
            </div>
            <div class="metric-item">
              <span class="metric-label">This Month:</span>
              <span class="metric-value"><?= e($stats->concernsThisMonth) ?> concerns</span>
            </div>
          </div>
        </div>
      </div>
    </div><?php endif; ?>

    
    <?php if (isAdmin()): ?><div class="user-management" id="user-management-section">
      <div class="section-header">
        <h2>User Management</h2>
        <button class="btn btn-outline" data-href="<?= e(url('dashboard')) ?>">Refresh</button>
      </div>

      <?php if ($userActionMessage): ?><p class="user-success"><?= e($userActionMessage) ?></p><?php endif; ?>
      <?php if ($userActionError): ?><p class="user-error"><?= e($userActionError) ?></p><?php endif; ?>

      <details class="add-user"><summary>Add User</summary><form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="create-user"><div class="form-grid"><input name="username" placeholder="Username" aria-label="New username" required><input name="email" type="email" placeholder="Email" aria-label="New email" required><input name="password" type="password" placeholder="Password" aria-label="New password" minlength="6" required><input name="firstName" placeholder="First name" aria-label="New first name" required><input name="lastName" placeholder="Last name" aria-label="New last name" required><select name="role" aria-label="New user role"><option value="user">Student / user</option><option value="security">Security</option></select></div><button class="btn btn-primary" type="submit">Create User</button></form></details>
      <?php if ((count($users) > 0)): ?><div class="users-list">
        <?php foreach ($users as $user): ?><div class="user-card">
          <?php if (($editingUserId !== $user->id)): ?><div class="user-row">
            <div class="user-main">
              <h3><?= e($user->username) ?></h3>
              <p><?= e($user->profile->firstName) ?> <?= e($user->profile->lastName) ?> | <?= e($user->email) ?></p>
              <p class="user-role"><?= e(ucwords((string)$user->role)) ?></p>
            </div>
            <div class="user-actions">
              <button class="btn btn-outline" data-href="<?= e(url('dashboard', ['editUser' => $user->id])) ?>" <?= isAdminUser($user) ? 'disabled' : '' ?>>Edit</button>
              <button class="btn btn-danger" data-action="delete-user" data-id="<?= e($user->id) ?>" <?= isAdminUser($user) ? 'disabled' : '' ?>>Delete</button>
            </div>
          </div><?php endif; ?>

          <?php if (($editingUserId === $user->id)): ?><div class="user-edit-form">
            <div class="form-grid">
              <input type="text" value="<?= e($editUserForm->username) ?>" name="username" placeholder="Username">
              <input type="email" value="<?= e($editUserForm->email) ?>" name="email" placeholder="Email">
              <input type="text" value="<?= e($editUserForm->password) ?>" name="password" placeholder="Password">
              <select name="role">
                <option value="<?= e('user') ?>" <?= $editUserForm->role === 'user' ? 'selected' : '' ?>>User</option>
                <option value="<?= e('security') ?>" <?= $editUserForm->role === 'security' ? 'selected' : '' ?>>Security</option>
              </select>
              <input type="text" value="<?= e($editUserForm->firstName) ?>" name="firstName" placeholder="First name">
              <input type="text" value="<?= e($editUserForm->lastName) ?>" name="lastName" placeholder="Last name">
              <input type="text" value="<?= e($editUserForm->phone) ?>" name="phone" placeholder="Phone">
              <input type="text" value="<?= e($editUserForm->studentId) ?>" name="studentId" placeholder="Student ID">
              <input type="text" value="<?= e($editUserForm->department) ?>" name="department" placeholder="Department">
              <input type="text" value="<?= e($editUserForm->position) ?>" name="position" placeholder="Position">
            </div>
            <div class="user-actions">
              <button class="btn btn-primary" data-action="save-user" data-id="<?= e($user->id) ?>">Save</button>
              <button class="btn btn-outline" data-href="<?= e(url('dashboard')) ?>">Cancel</button>
            </div>
          </div><?php endif; ?>
        </div><?php endforeach; ?>
      </div><?php endif; ?>

      
    </div><?php endif; ?>

    
    <div class="recent-concerns">
      <div class="section-header">
        <h2>Recent Concerns</h2>
        <button class="btn btn-outline" data-href="<?= e(url('concerns')) ?>">View All</button>
      </div>

      <?php if ((count($recentConcerns) > 0)): ?><div class="concerns-list">
        <?php foreach ($recentConcerns as $concern): ?><div class="concern-card" data-href="<?= e(url('details', ['id' => $concern->id])) ?>">
          <div class="concern-header">
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
          
          <div class="concern-content">
            <p class="concern-description"><?= e($concern->description) ?></p>
            <div class="concern-meta">
              <span class="location">Location: <?= e($concern->location->address) ?></span>
              <span class="date">Reported: <?= e(fmtdate($concern->reportedAt)) ?></span>
            </div>
          </div>
        </div><?php endforeach; ?>
      </div><?php endif; ?>

      <?php if ((count($recentConcerns) === 0)): ?><div class="empty-state">
        <p>No concerns reported yet.</p>
        <?php if ($currentUser): ?><button class="btn btn-primary" data-href="<?= e(url('report')) ?>">Report First Concern</button><?php endif; ?>
      </div><?php endif; ?>
    </div>

    
    <div class="quick-actions">
      <h2>Quick Actions</h2>
      <div class="actions-grid">
        <?php if ($currentUser): ?><div class="action-card" data-href="<?= e(url('report')) ?>">
          <div class="action-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m-1 4h2v5h5v2h-5v5h-2v-5H6v-2h5z"/></svg>
          </div>
          <h3>Report New Concern</h3>
          <p>Submit a new concern report</p>
        </div><?php endif; ?>

        <div class="action-card" data-href="<?= e(url('map')) ?>">
          <div class="action-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M2 5 8 2l8 3 6-3v17l-6 3-8-3-6 3z M8 4v13 M16 7v13"/></svg>
          </div>
          <h3>View Concern Map</h3>
          <p>See concerns on campus map</p>
        </div>

        <div class="action-card" data-href="<?= e(url('concerns')) ?>">
          <div class="action-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M3 4h3v3H3z M8 4h13v3H8z M3 10h3v3H3z M8 10h13v3H8z M3 16h3v3H3z M8 16h13v3H8z"/></svg>
          </div>
          <h3>All Concerns</h3>
          <p>Browse all concern reports</p>
        </div>

        <?php if (isAdmin()): ?><div class="action-card" data-href="<?= e(url('dashboard')) ?>">
          <div class="action-icon">
            <svg class="icon-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M8 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M1 21v-4c0-6 14-6 14 0v4z M17 3a4 4 0 0 1 0 8 M17 13c5 0 6 2 6 4v4h-6z"/></svg>
          </div>
          <h3>User Management</h3>
          <p>Manage system users</p>
        </div><?php endif; ?>
      </div>
    </div>
  </div>
</div><?php endif; ?>


