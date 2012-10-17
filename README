Rails like routing for PHP
==========================

Installation
------------
First, navigate to the directory that contains the script that you want to use  
as the main entry point for your application.

Basically, the installation comes down to: setup the .htaccess file, and
include 'router.php' from your main script.

In smaller steps, this would translate to:

1. Put router.php somewhere where it can be included from that script
2. Copy 'htaccess.txt' to the directory that contains your entry script
3. Rename the 'htaccess.txt' file to '.htaccess'
4. The default 'htaccess.txt' file assumes your main script is index.php. If that's not the case, change accordingly.    
5. Include the 'router.php' file from your main script
6. Set up the routes. Examples below or in 'example/index.php'

Examples
--------

	require('router.php');
	
	$r = new Router();
	
	// The default page people will see, e.g. this is a
	// mapping for http://<location>/example
	// -> runs HelloController->overview()
	$r->map("", "Hello::overview");
	
	// mapping for http://<location>/example/hello/en
	// -> runs HelloController->world()
	$r->map("hello", "Hello::world");
	$r->map("hello/en", "Hello::world");
	
	// mapping for http://<location>/example/hello/fr
	// -> runs HelloController->monde()
	$r->map("hello/fr", "Hello::monde");
	
	// mapping for http://<location>/example/<filename>.<txt|json>
	// -> runs FileController->download($filename, $ext) 
	//    where $filename matches <filename> and $ext is either 'txt' or 'json'
	$r->map(":filename\.:ext",
			"File::download", 
			// regular expressions determine what is valid for 'filename' and 'ext'
			array("filename"=>'[\w\d_-]+', "ext"=>"(txt|json)"));
	
	// generic mapping for http://<location>/example/<controller>/<action>
	// -> for example http://<location>/example/person/all will run 
	//    PersonController->all()
	// -> or http://<location>/example/organisation/add will run
	//    OrganisationController->add()
	$r->map(":controller/:action");
	
	// generic mapping for http://<location>/example/<controller>/<id>
	// -> for example http://<location>/example/person/2 will run 
	//    PersonController->view(2)
	// -> or http://<location>/example/organisation/3 will run
	//    OrganisationController->view(3)
	$r->map(":controller/:id", 
			array('action'=>'view'), 
			array("id"=>"[0-9]+")); // only allow numeric values for 'id'
	
	$r->run();



Changelog
---------
Based on http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
but extended in significant ways:

1. Can now be deployed in a subdirectory, not just the domain root
2. Will now call the indicated controller & action. Named arguments are
   converted to similarly method arguments, i.e. if you specify :id in the
   URL mapping, the value of that parameter will be provided to the method's
   '$id' parameter, if present.
3. Will now allow URL mappings that contain a '?' - useful for mapping JSONP urls
4. Should now correctly deal with spaces (%20) and other stuff in the URL
