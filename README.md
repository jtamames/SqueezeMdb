# Installation instructions (Assuming Ubuntu 14.01 or higher).

### You need to be a super-user to do this!

sudo su

### Install Apache + MySql + PHP
apt-get update  
apt-get install lamp-server^ # Set up the MySQL root password, and remember it.

add-apt-repository ppa:ondrej/php  
apt-get update  
apt-get install -y php5.6 php5.6-mcrypt php5.6-mbstring php5.6-curl php5.6-cli php5.6-mysql php5.6-gd php5.6-intl php5.6-xsl php5.6-zip libapache2-mod-php5.6

### Configure PHP
vi /etc/php/5.6/apache2/php.ini  
#Line 372: Set max_execution_time to 30000.  
#Line 660: Set post_max_size to 1200M.  
#Líne 820: Set upload_max_filesize to 1000M.  

### Restart apache
a2dismod php5  
a2enmod php5.6  
service apache2 restart  

### Create MySQL database.
mysql -u root -p -e "create database squeezem"  

### Clone SqueezeMdb repository.
cd /var/www/html  
git clone https://github.com/jtamames/SqueezeMdb  

### Set up MySQL datababase.
cat /var/www/html/SqueezeMdb/sql/game.sql | mysql -u root -p squeezem  
mysql -u root -p  
CREATE USER 'your_user_name'@'localhost' IDENTIFIED BY 'your_password';  
GRANT ALL PRIVILEGES ON squeezem.* TO 'your_user_name'@'localhost';  

### Configure SqueezeMdb
vi /var/www/html/SqueezeMdb/application/config/database.php  
#Line 79: Set 'username' to 'your_user_name' from the previous step.  
#Line 80: Set 'password', to 'your_password' from the previous step.  

### Grant permissions.
chmod -R 755 /var/www/html/SqueezeMdb  
chown www-data /var/www/html/SqueezeMdb/application/temp/  
chown www-data /var/www/html/SqueezeMdb/application/cache/  
chmod 770 /var/www/html/SqueezeMdb/application/temp/  
chmod 770 /var/www/html/SqueezeMdb/application/cache/  

