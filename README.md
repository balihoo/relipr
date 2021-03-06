relipr
======

REcipient LIst PRovider - Reference Implementation

This is a reference implementation and test endpoint for Balihoo's Recipient List Provider API. The code in this example is just that, an example. It is not meant to be copied and used in a production application. It is used to test endpoint clients and as a tutorial for understanding the API.

Install on Ubuntu Server
------------------------
The following shell commands should get you up and running on a clean Ubuntu server. This worked on a fresh Amazon EC2 micro using Ubuntu 12.04 64 bit. Run the following commands in bash from the directory where the server code will be installed.

1. Make sure your server is up to date:

		sudo apt-get update

2. Install the required packages:

		sudo apt-get -y install apache2 php5 sqlite3 php5-sqlite php5-curl git

3. Pull down the server code:

		git clone git://github.com/pauldprice/relipr.git

4. Move the www directory to the side and link it to relipr

		sudo mv /var/www/ /var/wwwbak
		sudo ln -s $(pwd)/relipr/web/ /var/www

5. Update the apache configuration

		cat <<APACHECONFIG > /tmp/apacheconfig
		<VirtualHost *:80>
			ServerAdmin webmaster@localhost

			DocumentRoot /var/www
			<Directory />
				Options FollowSymLinks
				AllowOverride None
			</Directory>
			<Directory /var/www/>
				Options FollowSymLinks MultiViews
				AllowOverride All
				Order allow,deny
				allow from all
			</Directory>

			ErrorLog \${APACHE_LOG_DIR}/error.log
			LogLevel warn
			CustomLog \${APACHE_LOG_DIR}/access.log combined

		</VirtualHost>
		APACHECONFIG
		sudo cp /etc/apache2/sites-available/default /etc/apache2/sites-available/default.bak
		sudo mv /tmp/apacheconfig /etc/apache2/sites-available/default

6. Turn on mod rewrite and headers

		sudo ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load
		sudo ln -s /etc/apache2/mods-available/headers.load /etc/apache2/mods-enabled/headers.load

7. Restart the apache server to take the new settings

		sudo apache2ctl -k restart

8. Run the configuration script

		cd relipr/
		./configure.sh

9. Open your web browser and navigate to your server's root directory (user: test, pass: letmein)
10. Click **Refresh source data file** -> wait for it to load -> hit the back button
11. Click **Refresh Database** -> wait for it -> hit back button
12. Refresh the page, make sure all the checklist items are blue

13. Schedule cron jobs to kick of background processing

		echo "* * * * *  ubuntu curl -sX POST 'http://test:letmein@localhost/jobs/callback' > /dev/null 2>&1" > /tmp/reliprcron
		echo "*/2 * * * *  ubuntu curl -sX POST 'http://test:letmein@localhost/jobs/count' > /dev/null 2>&1" >> /tmp/reliprcron
		echo "*/3 * * * *  ubuntu curl -sX POST 'http://test:letmein@localhost/jobs/ready' > /dev/null 2>&1" >> /tmp/reliprcron

		sudo mv /tmp/reliprcron /etc/cron.d/relipr
		sudo chown root:root /etc/cron.d/relipr
		sudo chmod 0644 /etc/cron.d/relipr

You are done!!
