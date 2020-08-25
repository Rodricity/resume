# PHP-bar-graph
Bar graphs for PHP using php-gd. This little script allows you to generate bar graph as raw images, specially usefull when you need to export or generate PDF files in a programatic way with no browser capabilities.

Its not meant to be complicated nor fancy, it just get the job done and works as you would expect; You give it an array in the form of ["Label" => value] and it will draw that for you.

Example code for generating PNG image file:

```php
include("../BarGraph.php");

//Data for the bar graph
$data = ["Ene" => 114.3, "Feb" => 55, "Mar" => 20, "Abr" => 100];

//Creation of the bar graph. we manually set the size
$graph = new BarGraph(400, 300);

//Data is loaded
$graph->setData($data);

//File handler creation. This is were the image will be created.
$file_handler = fopen("bar_graph_image.png", "w");

//The file contents is the bar graph output
$file_contents = $graph->generate();

//Then we just need to fill the file
fwrite($file_handler, $file_contents);
fclose($file_handler);
```

![alt text](https://github.com/Rodricity/PHP-bar-graph/raw/master/demo/bar_graph_image.png "Output image file")

Example code for generating HTML img tag:

```php
include("../BarGraph.php");

//Data for the bar graph
$data = ["Ene"=> 114.3, "Feb" => 55, "Mar" => 20, "Abr" => 100];

//Creation of the bar graph. we manually set the size
$graph = new BarGraph(400, 300);

//Data is loaded
$graph->setData($data);

//And we generate the bar graph. with the parameter we specify for HTML img tag creation.
echo $graph->generate(true);
```

![alt text](https://github.com/Rodricity/PHP-bar-graph/raw/master/demo/html_output_screenshot.png "HTML img tag output")