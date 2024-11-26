---
title: Improve mail sender by using the private file system over message queue
issue: NEXT-00000
author: Benjamin Wittwer
author_email: benjamin.wittwer@a-k-f.de
author_github: akf-bw
---
# Core
* Changed `src/Core/Content/Mail/Service/AbstractMailSender.php` to deprecate the `envelope` parameter
* Changed `src/Core/Content/Mail/Service/MailSender.php` so it no longer directly sends the mail to the Symfony mailer, but writes the serialized mail to the private file system & dispatches a `SendMailMessage` to the message bus
