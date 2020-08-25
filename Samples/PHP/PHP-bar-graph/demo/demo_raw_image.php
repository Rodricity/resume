<?php

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
?>