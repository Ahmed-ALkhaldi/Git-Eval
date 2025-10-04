<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resubmit Enrollment Certificate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header .icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }

        .alert.error {
            background-color: #ffeaea;
            border-color: #e74c3c;
            color: #c0392b;
        }

        .alert.info {
            background-color: #e8f4f8;
            border-color: #3498db;
            color: #2980b9;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input[type="file"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn.secondary {
            background: #6c757d;
            color: white;
        }

        .btn.secondary:hover {
            background: #5a6268;
        }

        .back-link {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            text-decoration: none;
            font-size: 18px;
            padding: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="{{ route('student.dashboard') }}" class="back-link">
        <i class="fas fa-arrow-right"></i>
    </a>

    <div class="container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-file-upload"></i>
            </div>
            <h1>Resubmit Enrollment Certificate</h1>
        </div>

        <div class="alert error">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Your previous request was rejected.</strong> Please resubmit a clear and valid enrollment certificate.
        </div>

        @if(session('error'))
            <div class="alert error">
                <i class="fas fa-times-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('student.resubmit-certificate.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-group">
                <label for="enrollment_certificate">
                    <i class="fas fa-file-pdf"></i>
                    New Enrollment Certificate *
                </label>
                <input type="file" 
                       id="enrollment_certificate" 
                       name="enrollment_certificate" 
                       accept=".pdf,.jpg,.jpeg,.png" 
                       required>
                <div class="file-info">
                    <i class="fas fa-info-circle"></i>
                    Accepted files: PDF, JPG, PNG (Maximum 4 MB)
                </div>
            </div>

            <div class="form-group">
                <label for="resubmission_note">
                    <i class="fas fa-comment"></i>
                    Additional Note (Optional)
                </label>
                <textarea id="resubmission_note" 
                         name="resubmission_note" 
                         placeholder="Add any notes or clarifications about the new certificate..."></textarea>
            </div>

            <div class="buttons">
                <button type="submit" class="btn primary">
                    <i class="fas fa-upload"></i>
                    Resubmit Certificate
                </button>
                <a href="{{ route('student.dashboard') }}" class="btn secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        // تحسين تجربة رفع الملف
        document.getElementById('enrollment_certificate').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileInfo = document.querySelector('.file-info');
                const size = (file.size / 1024 / 1024).toFixed(2);
                fileInfo.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                    File selected: ${file.name} (${size} MB)
                `;
            }
        });
    </script>
</body>
</html>
