<div class="register-container">
  <div class="register-card">
    <div class="register-header">
      <div class="ub-logo">
        <h1>University of Baguio</h1>
        <p>Concern Report System</p>
      </div>
      <h2>Create Your Account</h2>
    </div>

    <form class="register-form" method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="register">
      <div class="form-row">
        <div class="form-group">
          <label for="firstName">First Name *</label>
          <input type="text" id="firstName" value="<?= e($registerData->firstName) ?>" name="firstName" placeholder="Enter your first name" required>
        </div>
        <div class="form-group">
          <label for="lastName">Last Name *</label>
          <input type="text" id="lastName" value="<?= e($registerData->lastName) ?>" name="lastName" placeholder="Enter your last name" required>
        </div>
      </div>

      <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" value="<?= e($registerData->username) ?>" name="username" placeholder="Choose a username" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address *</label>
        <input type="email" id="email" value="<?= e($registerData->email) ?>" name="email" placeholder="Enter your email" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password *</label>
          <div class="password-input">
            <input type="<?= e(($showPassword ? 'text' : 'password')) ?>" id="password" value="<?= e($registerData->password) ?>" name="password" placeholder="Create a password" required>
            <button type="button" class="toggle-password" data-password="password">
              <?= e(($showPassword ? 'Hide' : 'Show')) ?>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirm Password *</label>
          <div class="password-input">
            <input type="<?= e(($showConfirmPassword ? 'text' : 'password')) ?>" id="confirmPassword" value="<?= e($registerData->confirmPassword) ?>" name="confirmPassword" placeholder="Confirm your password" required>
            <button type="button" class="toggle-password" data-password="confirmPassword">
              <?= e(($showConfirmPassword ? 'Hide' : 'Show')) ?>
            </button>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" value="<?= e($registerData->phone) ?>" name="phone" placeholder="+63-XXX-XXX-XXXX">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="studentId">Student ID (Optional)</label>
          <input type="text" id="studentId" value="<?= e($registerData->studentId) ?>" name="studentId" placeholder="e.g., 2024-12345">
        </div>
        <div class="form-group">
          <label for="department">Department (Optional)</label>
          <input type="text" id="department" value="<?= e($registerData->department) ?>" name="department" placeholder="e.g., College of Engineering">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" <?= $isLoading ? 'disabled' : '' ?>>
          <?= e(($isLoading ? 'Creating Account...' : 'Create Account')) ?>
        </button>
      </div>

      <?php if ($errorMessage): ?><div class="error-message">
        <?= e($errorMessage) ?>
      </div><?php endif; ?>

      <?php if ($successMessage): ?><div class="success-message">
        <?= e($successMessage) ?>
      </div><?php endif; ?>
    </form>

    <div class="register-footer">
      <p>Already have an account? <a data-href="<?= e(url('login')) ?>">Login here</a></p>
    </div>
  </div>
</div>
