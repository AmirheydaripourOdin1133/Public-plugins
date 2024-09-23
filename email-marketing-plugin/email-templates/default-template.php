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
            background: rgb(248,248,248);
        }
         .container {
            width: 80%;
            margin: 0 auto;
        }
        
        .header, .footer {  padding: 10px 0;
        text-align: center;
        }
        .footer {
          background-color: #054078;
          color: #fff;
          font-weight: 800;
          font-size: 16px;
          border-radius: 7px;
        }
        .content { 
        margin: 20px 0;
        font-size:15px;
        color: #505050;
        line-height:1.8em;
        text-align:right;
        
        }
        
        h1{
          font-weight: 800;
          font-size:26px;
          background: #d1ab66;
        color: #fff;
        padding:8px 0;
        border-radius: 8px;
        }
        
        h3{
          font-weight: 600;
          color: #d1ab66;
          font-size:18px;
        }
        
        .socialSectionInner {
            background-color: #054078;
            display: flex;
            align-items: center;
            border-radius: 8px 8px 0 0;
            width: max-content;
            margin: auto;
                margin-top: auto;
            margin-top: 50px;
            box-shadow: 0px 0px 10px #9a9a9a;
}

    .socialSectionInner .iconSocialPart {
        display: flex;
        width: -moz-max-content;
        width: max-content;
        height: -moz-max-content;
        height: max-content;
        padding: 10px;
            padding-right: 10px;
    }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
          <div class="Logo">
              <a href="https://visaline.net/" target="_blank">
              <img src="https://visaline.net/wp-content/uploads/2024/07/logo.png" alt="">
              </a>
          </div>
            <h1>شرکت مهاجرتی ویزالاین</h1>
             <h3><?php echo $subject; ?></h3>
        </div>
        <div class="content">
            <?php echo $body; ?>
        </div>
<div class="socialSectionInner">
				<a href="https://api.whatsapp.com/send/?phone=16478600005&amp;text&amp;type=phone_number&amp;app_absent=0" class="iconSocialPart" target="_blank">
                    <img src="https://visaline.net/wp-content/uploads/2024/07/what.png" alt="">
				</a>
				<a href="mailto:info@visaline.ca" class="iconSocialPart" target="_blank">
                    <img src="https://visaline.net/wp-content/uploads/2024/07/mail.png" alt="">

				</a>
				<a href="https://www.instagram.com/visaline.ca/" class="iconSocialPart" target="_blank">
                    <img src="https://visaline.net/wp-content/uploads/2024/07/insta.png" alt="">

				</a>
				<a href="https://t.me/VisaLineimmigrationgroup" class="iconSocialPart" target="_blank">
                <img src="https://visaline.net/wp-content/uploads/2024/07/tel.png" alt="">

				</a>
			</div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> بعضی گام ها را نباید تنها برداشت</p>
        </div>
    </div>
</body>
</html>
