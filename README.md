# crollerProducts 

## About
this project is a project to a client,
*what this poject doing?*
it is simple, the project read a csv file and update the Database table (produtos)
and other thing the parts of the project is in Portuguese then the basic of the portuguese is necessary

## Configuration
OK but how configure?
**Is simple**
here is the stpes

* Database connect Configuration
   * oop/Database/Connect.php 
   * alter the __construct function to the url necessary
   * create the tables necessary the tables are in the oop/Columns/CsvUpdate.php file, the list of the tables 
* Files configuration
   * create the files/ directory
   * move the csv file to the files/ 
   * create the log.txt in files/log.txt this file is necessary to loga 
* Database Tables
    * produtos 
    * lojas_tributacao
    * produtos_ean
    * ws_ncm
    * ws_cest
    * ws_natureza_receita
    * ws_ajustes_docto_fiscal
    * figura_fiscal_pis_cofins
## Dependencies 
OK you already configure the croller now what are the dependencies?
here is a list 

* Composer
* PHP >= 7.00 

### How to use without composer 

#### CsvUpdate requires
OK you no have composer then is necessary alter some files 
first open the oop/Columns/CsvUpdate.php 
comment the _**require**_ line and add the line *require '../Database/Connect.php'*
and the comment the _use_ line  

#### main.php 
comment the _**require**_ line and add the *require '../oop/Columns/CsvUpdate.php';* now alter the *new Files\Columns\CsvUpdate;* line to *new CsvUpdate*;

OK it is all necessary to use without composer autoload 
