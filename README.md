# ΣΗΔΕ
Μία εφαρμογή για τη διασύνδεση οποιασδήποτε μονάδας του δημοσίου με το Κεντρικό Σύστημα Ηλεκτρονικής Διακίνησης Εγγράφων του Υπουργείου Ψηφιακής Διακυβέρνησης.

# Απαιτήσεις
PHP 7.2+, Composer

# Χαρακτηριστικά
* Σύνδεση με SQLite3/MySQL
* Login/Logout
* Απεριόριστοι Φορείς
* Απεριόριστα EndPoints
* Απεριόριστοι χρήστες
* Απεριόριστα μηνύματα
* Διαβαθμίσεις μέχρι άκρως απόρρητο
* Αυτόματο πρωτόκολλο μέσω διεπαφής
* Αποστολείς, Κοινοποιήσεις, Κρυφές Κοινοποιήσεις, Εσωτερική Διανομή
* Ρόλοι χρηστών ανά φορέα/ανά endpoint
* Push Notifications
* Διεπαφή με το ΥΨηΔ 
* Κρυπτογράφηση Διαβαθμισμένων Εγγράφων
* Βιομετρικό Login
* Ψηφιακές Υπογραφές με εμφάνιση ή μη στο PDF
* Πολλαπλοί υπογράφοντες
* API Keys για διαλειτουργικότητα με άλλα συστήματα
* Κανόνες εισερχομένων
* Δυνατότητα αντιστοίχισης εικονικού email σε endpoint
* Κρυπτογραφημένα έγγραφα
* Θυρίδες για κάθε χρήστη

# Βιβλιοθήκες σε χρήση
* Web Push - https://github.com/web-push-libs/web-push-php
* TCPDF - https://github.com/tecnickcom/TCPDF.git
* PHPMailer - https://github.com/PHPMailer/PHPMailer
* WebAuthn - https://github.com/lbuchs/WebAuthn
* PDF Parser - https://github.com/smalot/pdfparser
* vcard - https://github.com/jeroendesloovere/vcard
* Updater - https://github.com/visualappeal/php-auto-update
* Mail Parser - https://github.com/zbateson/mail-mime-parser

# Εγκατάσταση
* Εγκατάσταση όλων των αρχείων σε φάκελο στον server σας (Apache/Nginx, PHP 7.1+)
* Εγκατάσταση βιβλιοθηκών:
    *  *composer require --ignore-platform-reqs phpmailer/phpmailer*
    *  *composer require --ignore-platform-reqs  minishlink/web-push*
    *  *composer require --ignore-platform-reqs  tecnickcom/tcpdf*
    *  *composer require --ignore-platform-reqs  lbuchs/webauthn*
    *  *composer require --ignore-platform-reqs  smalot/pdfparser*
    *  *composer require --ignore-platform-reqs jeroendesloovere/vcard*
    *  *composer require --ignore-platform-reqs visualappeal/php-auto-update*
    *  *composer require --ignore-platform-reqs zbateson/mail-mime-parser*
* Ρύθμιση παραμέτρων στο config-example.php και μετονομασια σε configuration.php
* Ρύθμιση παραμέτρων στο notify2.js
* Πρέπει να υπάρχει write access στο φάκελο του TCPDF για χρήση των γραμματοσειρών
* Πρέπει στο configuration.php το Temp Database να είναι σε writable folder (για να γίνονται δεκτά e-mails)

# Ρυθμίσεις config-example.php/configuration.php
Μετά την εγκατάσταση πρέπει να μετονομαστεί το αρχείο config_example.php σε configuration.php και να ρυθμιστούν εκεί οι παράμετροι του server.




