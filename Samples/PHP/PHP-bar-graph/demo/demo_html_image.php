<?php

include("../BarGraph.php");

//Data for the bar graph
$data = ["Ene" => 114.3, "Feb" => 55, "Mar" => 20, "Abr" => 100];

//Creation of the bar graph. we manually set the size
$graph = new BarGraph(400, 300);

//Data is loaded
$graph->setData($data);

//And we generate the bar graph. with the parameter we specify for HTML img tag creation.
echo $graph->generate(true);
?>