<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Supervisor Profile</title>
  <link rel="stylesheet" href="{{ asset('css/supervisor.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
    /* Additional styles for the profile edit form */
    .form-container {
      max-width: 600px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #374151;
    }
    
    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-group .help-text {
      font-size: 12px;
      color: #6b7280;
      margin-top: 4px;
    }
    
    .form-group.error input,
    .form-group.error select {
      border-color: #ef4444;
    }
    
    .form-group .error-message {
      color: #ef4444;
      font-size: 12px;
      margin-top: 4px;
    }
    
    .password-section {
      border-top: 1px solid #e5e7eb;
      padding-top: 20px;
      margin-top: 30px;
    }
    
    .password-section h3 {
      margin-bottom: 15px;
      color: #374151;
    }
    
    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 30px;
    }
    
    .btn-primary {
      background: #3b82f6;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .btn-primary:hover {
      background: #2563eb;
    }
    
    .btn-secondary {
      background: #6b7280;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      transition: background-color 0.3s ease;
    }
    
    .btn-secondary:hover {
      background: #4b5563;
    }
    
    .status-indicator {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
    }
    
    .status-active {
      background: #dcfce7;
      color: #166534;
    }
    
    .status-inactive {
      background: #fee2e2;
      color: #991b1b;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="brand">
      <i class="fa-solid fa-user-edit"></i>
      <span class="title">Edit Profile</span>
    </div>
    <div class="nav-actions">
      <a class="link" href="{{ route('supervisor.dashboard') }}">Dashboard</a>
      <form method="POST" action="{{ route('logout') }}" style="display:inline-block; margin-left:.75rem;">
        @csrf
        <button type="submit" class="link" style="background:none;border:none;padding:0;cursor:pointer;">
          Logout
        </button>
      </form>
    </div>
  </nav>

  <main class="container">
    <section class="page">
      <div class="header">
        <div class="title">
          <i class="fa-solid fa-user-cog"></i>
          <h1>Edit Supervisor Profile</h1>
        </div>
        <span class="subtitle">Update your account information and settings</span>
      </div>

      @if(session('success'))
        <div class="alert success">
          <i class="fa-solid fa-check-circle"></i>
          {{ session('success') }}
        </div>
      @endif

      @if(session('error'))
        <div class="alert error">
          <i class="fa-solid fa-exclamation-triangle"></i>
          {{ session('error') }}
        </div>
      @endif

      <div class="form-container">
        <form method="POST" action="{{ route('supervisor.profile.update') }}">
          @csrf
          @method('PATCH')

          <!-- Current Status Display -->
          <div class="form-group">
            <label>Current Status</label>
            <div class="status-indicator {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
              <i class="fa-solid fa-{{ $user->is_active ? 'check-circle' : 'times-circle' }}"></i>
              {{ $user->is_active ? 'Active' : 'Inactive' }}
            </div>
          </div>

          <!-- Email -->
          <div class="form-group {{ $errors->has('email') ? 'error' : '' }}">
            <label for="email">
              <i class="fa-solid fa-envelope"></i>
              Email Address *
            </label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              value="{{ old('email', $user->email) }}" 
              required
            >
            @if($errors->has('email'))
              <div class="error-message">{{ $errors->first('email') }}</div>
            @endif
          </div>

          <!-- Active Status -->
          <div class="form-group {{ $errors->has('active_status') ? 'error' : '' }}">
            <label for="active_status">
              <i class="fa-solid fa-toggle-on"></i>
              Account Status *
            </label>
            <select id="active_status" name="active_status" required>
              <option value="1" {{ old('active_status', $user->is_active) == '1' ? 'selected' : '' }}>
                Active - Can receive project requests
              </option>
              <option value="0" {{ old('active_status', $user->is_active) == '0' ? 'selected' : '' }}>
                Inactive - Will not receive new requests
              </option>
            </select>
            <div class="help-text">
              Inactive supervisors will not appear in the student's supervisor selection list.
            </div>
            @if($errors->has('active_status'))
              <div class="error-message">{{ $errors->first('active_status') }}</div>
            @endif
          </div>

          <!-- Password Section -->
          <div class="password-section">
            <h3>
              <i class="fa-solid fa-key"></i>
              Change Password (Optional)
            </h3>

            <!-- Current Password -->
            <div class="form-group {{ $errors->has('current_password') ? 'error' : '' }}">
              <label for="current_password">
                <i class="fa-solid fa-lock"></i>
                Current Password
              </label>
              <input 
                type="password" 
                id="current_password" 
                name="current_password"
                placeholder="Enter current password to change"
              >
              <div class="help-text">Required only if you want to change your password</div>
              @if($errors->has('current_password'))
                <div class="error-message">{{ $errors->first('current_password') }}</div>
              @endif
            </div>

            <!-- New Password -->
            <div class="form-group {{ $errors->has('new_password') ? 'error' : '' }}">
              <label for="new_password">
                <i class="fa-solid fa-key"></i>
                New Password
              </label>
              <input 
                type="password" 
                id="new_password" 
                name="new_password"
                placeholder="Enter new password"
              >
              <div class="help-text">
                Must be at least 8 characters with uppercase, lowercase, numbers, and symbols
              </div>
              @if($errors->has('new_password'))
                <div class="error-message">{{ $errors->first('new_password') }}</div>
              @endif
            </div>

            <!-- Confirm New Password -->
            <div class="form-group {{ $errors->has('new_password_confirmation') ? 'error' : '' }}">
              <label for="new_password_confirmation">
                <i class="fa-solid fa-key"></i>
                Confirm New Password
              </label>
              <input 
                type="password" 
                id="new_password_confirmation" 
                name="new_password_confirmation"
                placeholder="Confirm new password"
              >
              @if($errors->has('new_password_confirmation'))
                <div class="error-message">{{ $errors->first('new_password_confirmation') }}</div>
              @endif
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="submit" class="btn-primary">
              <i class="fa-solid fa-save"></i>
              Update Profile
            </button>
            <a href="{{ route('supervisor.dashboard') }}" class="btn-secondary">
              <i class="fa-solid fa-times"></i>
              Cancel
            </a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script>
    // Show/hide password fields based on current password input
    document.getElementById('current_password').addEventListener('input', function() {
      const newPasswordField = document.getElementById('new_password');
      const confirmPasswordField = document.getElementById('new_password_confirmation');
      
      if (this.value.length > 0) {
        newPasswordField.required = true;
        confirmPasswordField.required = true;
      } else {
        newPasswordField.required = false;
        confirmPasswordField.required = false;
        newPasswordField.value = '';
        confirmPasswordField.value = '';
      }
    });

    // Password strength indicator
    document.getElementById('new_password').addEventListener('input', function() {
      const password = this.value;
      const helpText = this.parentNode.querySelector('.help-text');
      
      if (password.length === 0) {
        helpText.textContent = 'Must be at least 8 characters with uppercase, lowercase, numbers, and symbols';
        helpText.style.color = '#6b7280';
        return;
      }
      
      const checks = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        numbers: /\d/.test(password),
        symbols: /[!@#$%^&*(),.?":{}|<>]/.test(password)
      };
      
      const passedChecks = Object.values(checks).filter(Boolean).length;
      
      if (passedChecks === 5) {
        helpText.textContent = '✅ Strong password';
        helpText.style.color = '#059669';
      } else if (passedChecks >= 3) {
        helpText.textContent = '⚠️ Medium strength - consider adding more complexity';
        helpText.style.color = '#d97706';
      } else {
        helpText.textContent = '❌ Weak password - needs more requirements';
        helpText.style.color = '#dc2626';
      }
    });
  </script>
</body>
</html>
