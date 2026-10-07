EVERYTHING ELSE IS ALREADY DONE FOR YOU.
This is the one remaining manual step - it takes about 2 minutes.

WHY THIS STEP EXISTS
---------------------
PHPMailer's main file is thousands of lines long. There's no reliable way
to hand you that much raw code through a chat conversation, so instead
you'll grab the real, official files directly from the source.

WHAT TO DO
-----------
1. Go to: https://github.com/PHPMailer/PHPMailer/releases
2. Click the top (most recent) release, then download "Source code (zip)"
3. Extract the ZIP anywhere on your computer
4. Inside the extracted folder, find the "src" folder
5. Copy these THREE files out of that "src" folder:
     - PHPMailer.php
     - SMTP.php
     - Exception.php
6. Paste all three files directly into THIS folder
   (library-management/lib/PHPMailer/) - right next to this README

When you're done, this folder should contain:
   lib/PHPMailer/PHPMailer.php
   lib/PHPMailer/SMTP.php
   lib/PHPMailer/Exception.php
   lib/PHPMailer/README.txt   (this file - harmless to leave)

That's it. The code that uses these files (includes/notifications.php)
is already written and already turned on (EMAIL_ENABLED = true in
config/config.php). Once these 3 files are in place and you've added
your Gmail App Password (see the main instructions), sending a
notification with the "Email" checkbox ticked will actually deliver.
