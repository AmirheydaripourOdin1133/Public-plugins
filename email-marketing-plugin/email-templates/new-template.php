<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $subject; ?></title>
    <style>
		* {
			font-family: Tahoma !important;
		}
        body, div { 
            font-family: Tahoma !important; line-height: 1.6;
            direction:rtl !important;
            background: #eee;
        }
 
        .container { width: 80%; margin: 0 auto; }
        .header, .footer { background: #f7f7f7; padding: 10px 0; text-align: center; }
        .content { margin: 20px 0; }
        .custom-style { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $subject; ?></h1>
        </div>
        <div class="content custom-style">
            <?php echo $body; ?>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Your Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>