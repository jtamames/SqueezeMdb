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
**L372:** set max_execution_time to 0  
**L382:** set max_input_time to -1  
**L393:** set memory_limit to 6G # smaller datasets should run with the default value of 128M.  
**L660:** set post_max_size to 10G # smaller datasets should run with 1200M or lower.  
**L820:** set upload_max_filesize to 10G # smaller datasets should run with 1200M or lower.  


### Restart apache
a2dismod php5  
a2enmod php5.6  
service apache2 restart  

### Create MySQL database
mysql -u root -p -e "create database squeezem"  

### Clone SqueezeMdb repository
cd /var/www/html  
git clone https://github.com/jtamames/SqueezeMdb  

### Set up MySQL datababase
cat /var/www/html/SqueezeMdb/sql/game.sql | mysql -u root -p squeezem  
mysql -u root -p  
CREATE USER 'your_user_name'@'localhost' IDENTIFIED BY 'your_password';  
GRANT ALL PRIVILEGES ON squeezem.* TO 'your_user_name'@'localhost';  

### Configure SqueezeMdb
vi /var/www/html/SqueezeMdb/application/config/config.php 
#Line 26: Set 'base_url' to <your.domain.name>/SqueezeMdb 
#The default is 'localhost/SqueezeMdb'. This will work if accessing from your computer, but not externally.    
#Change it to the full address of your computer to enable external access.    
vi /var/www/html/SqueezeMdb/application/config/database.php  
#Line 79: Set 'username' to 'your_user_name' from the previous step.  
#Line 80: Set 'password', to 'your_password' from the previous step.  

### Grant permissions.
chmod -R 755 /var/www/html/SqueezeMdb  
chown -R www-data:www-data /var/www/html/SqueezeMdb/application/temp/  
chown www-data:www-data /var/www/html/SqueezeMdb/application/cache/  
chmod -R 770 /var/www/html/SqueezeMdb/application/temp/  
chmod 770 /var/www/html/SqueezeMdb/application/cache/

### Enabling server logging (if required for debugging purposes)
chmod 770 /var/www/html/SqueezeMdb/application/logs # Logs will be stored in that folder.

# Creating databases for your SqueezeMeta projects.
1. Once installed the interface should be accesible at http://localhost/SqueezeMdb/index.php/Login
2. Login as the default admin user  
   User: squeezem@squeezem.com  
   Password: squeezem
3. Use the interface to create a non-admin user. Admin users can create new projects and new users. Non-admin users can access the project database and make queries.  
4. In order to create a new project, first generate the required files by running the *make-SqueezeMdb-files.py* script from SqueezeMeta as following:  
`python3 <installpath>/SqueezeMeta/utils/make-SqueezeMdb-files.py <PROJECT_DIR> <OUTPUT_DIR>`,  
where PROJECT_DIR is the directory holding the SqueezeMeta run. This files can be added to the new project using the web interface.
5. This new project can be assigned to any non-admin user/s. Once created, the project can be accessed by logging in as the non-admin user.
6. Parsing all the files and creating the MySQL database could take several minutes (around 10 min for the results of the SqueezeMeta test files).
