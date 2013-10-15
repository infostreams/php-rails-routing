<?php
require('../router.php');

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

// generic mapping for http://<location>/example/<controller>/<id>
// -> for example http://<location>/example/person/2 will run
//    PersonController->view(2)
// -> or http://<location>/example/organisation/3 will run
//    OrganisationController->view(3)
$r->map(":controller/:id",
	array('action'=>'view'),
	array("id"=>"[0-9]+")); // only allow numeric values for 'id'

// generic mapping for http://<location>/example/<controller>/<action>
// -> for example http://<location>/example/person/all will run 
//    PersonController->all()
// -> or http://<location>/example/organisation/add will run
//    OrganisationController->add()
$r->map(":controller/:action");

$r->run();


class HelloController {
	public function world() {
		echo "Hello world";
	}
	
	public function monde() {
		echo "Bonjour le monde";
	}
	
	public function overview() {
		$links = array(
			"hello/",
			"hello/en",
			"hello/fr",
			"some-filename.json",
			"some-filename.txt",
			"some-filename.xml",
			"person/2",
			"person/all",
			"organisation/5",
			"organisation/add",
			"image.jpg", // it won't interfere with getting existing external files
		);
		echo "Try some of these predefined routes:<ul>";
		foreach ($links as $l) {
			echo "<li><a href='$l'>$l</a></li>";
		}
		echo "</ul>";
	}
}

class FileController {
	public function download($filename, $ext) {
		$name = $filename . "." . $ext;
		switch(strtolower($ext)) {
			case "txt":
				$mimetype = "text/plain";
				$content = $this->getTxt($name);
				break;
			case "json":
				$mimetype = "application/json";
				$content = $this->getJson($name);
				break;
			default:
				break;
		}
		
		header("Content-Type: $mimetype");
		header("Content-Disposition: attachment; filename=\"$name\"");
		header("Content-Length: " . strlen($content));
		echo $content;
		die;
	}
	
	public function getTxt($filename) {
		return "This is an example file called $filename. Praesent id metus massa, ut blandit odio. Proin quis tortor orci. Etiam at risus et justo dignissim congue. Donec congue lacinia dui, a porttitor lectus condimentum.";
	}
	
	public function getJson($filename) {
		return json_encode(array("example"=>"This is an example file", "filename"=>$filename));
	}
}

class PersonController {
	public function view($id) {
		echo "This is person $id";
	}
	public function all() {
		echo "Shows a list of all people";
	}
}

class OrganisationController {
	public function view($id) {
		echo "This is organisation '$id'";
	}
	public function add() {
		echo "Add a new organisation";
	}
}
