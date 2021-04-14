Secure Hosting Payment Gateway for Prestashop 1.7.3.1+
======================================================

Manual Installation
-------------------

1. Download the zip 

2. Copy and paste the contents of "securehosting" folder into the "[prestashop_install]/modules" folder

3. Add the below text into trusted_modules_list.xml located in "[prestashop_install]/config/xml/" and if necessary also remove from the untrusted file
"<module name="securehosting"/>"

4. Navigate to the "Modules/Modules & Services" page
Search for 'SecureHosting', and click Install.

Once installed, click Configure and fill in the details for your SH account as below:

**SecureHosting account reference**
	- This is the reference for your Secure Hosting account. This is also known as the client login, you will find the value for this within the company details section of your Secure Hosting account.
**Second level security checkcode**
	- This is the second level security check code for your Secure Hosting account, it is a second unique identifier for your account. The value of your check code can be found within the company details section of your Secure Hosting account.
	
**SecureHosting template**
	- This is the file name of the payment page template you need to upload to your Secure Hosting account. The file name of the example template provided with this integration module is "prestashop_template.html". You can rename this file if you desire, you only need to ensure the name of the file you upload to your Secure Hosting account is correctly set here.

**SecureHosting Advanced Secuphrase**
- If you wish to use the Advanced Secuitems function, set the SecureHosting Advanced Secuphrase as is set in your SH account. 
- You will also need to activate the feature in your account by checking the setting "Activate Advanced Secuitems Security". If you do not wish to use this feature leave it set to blank.

5. Upload the template files to your Secure Hosting account within the File Manager section, using files included in the "forms" folder
- prestashop_template.html
- htmlgood.html
- htmlbad.html


Troubleshooting		
-------------------

- When I get transferred to the Secure Hosting Site the following message appears: "_The file SH2?????/ does not exist_"
    - You have not completed step 5 of the installation.

- When a transaction is submitted the following error is displayed: Merchant Implementation Error - Incorrect client SH reference and check code combination
    - You have entered an incorrect account reference or security checkcode into the Prestashop Admin interface.

- When I get transferred to the Secure Hosting site, the following message appears: Advanced Secuitems security check failed.
    - You have activated the Advanced Secuitems within your Secure Hosting account but not configured it correctly. Ensure the "Advanced Secuphrase" has been entered as above in step 4

Change Log
---------

#### Version 0.9.6
**Date**: _16/07/2018_

##### Details:
* Added support for Prestashop versions 1.7.3.1+
* AS hashes built locally.
