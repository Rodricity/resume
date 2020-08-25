<?php

/**
 * BarGraph class for creating bar braphs on PHP using php-gd
 *
 * @author Rodrigo Arias <rodrigo.arias.tapia@gmail.com>
 */
class BarGraph
	{
	/* size of the bar graph in pixels */
	private $height = 300;
	private $width = 600;
	private $lineWidth = 1;

	/* How many divisions of the Y axis are generated */
	private $gridLines_y = 5;

	/* Margin of the bar graph. Used for allmost all elements */
	private $margin = 10;

	/* Fonts, by default is used Roboto */
	private static $font_folder = __DIR__ . "/fonts/";
	private $font = "roboto_regular/Roboto-Regular-webfont.ttf";
	private $fontSize = 12;
	private $label_y_decimals = 2;

	/* Data for the bar graph */
	private $graphData = Array();

	/**
	 * Constructor for a new bar graph, the default size is 600*400 px
	 * @param type $width
	 * @param type $height
	 */
	public function __construct($width = 600, $height = 400)
		{
		$this->width = $width;
		$this->height = $height;

		//Image creation
		$this->chart = imagecreate($this->width, $this->height);

		//Colours for the background and the axis.
		$this->backgroundColor = imagecolorallocate($this->chart, 255, 255, 255);
		$this->axisColor = imagecolorallocate($this->chart, 60, 60, 60);

		$this->labelColor = $this->axisColor;
		$this->gridColor = imagecolorallocate($this->chart, 200, 200, 200);
		$this->barColor = imagecolorallocate($this->chart, 95, 191, 239);
		}

	/**
	 * Generates the corresponding bar graph for the input data
	 * @return String png raw data for creating an image
	 */
	public function generate($exportAsHtmlImage = false)
		{
		if (!is_array($this->graphData))
			{
			throw new Exception("The data specified for this bar graph is not valid. It must be an Array");
			}

		if (!count($this->graphData))
			{
			throw new Exception("This bar graph rendering engine requires at least one element in its data");
			}

		$fontFile = self::$font_folder . $this->font;

		//Each of the values labels are the corresponding array key.
		$dataLabels = array_keys($this->graphData);

		//And the values are the values themselves
		$dataValues = array_values($this->graphData);

		//We need the max value of Y axis in order to draw the correct Y grid divisions
		$maxValue = max($dataValues);

		//px -> pt. not really accurate but reliable enough
		$fontSizePt = $this->fontSize * 3 / 4;

		//The total graph height is: Margins+Y Label height+X Label height
		$gridHeight = $this->height - (($this->fontSize / 2) + $this->margin) - ($this->margin + $this->fontSize + $this->margin);

		//Fill the image with the background color
		imagefill($this->chart, 0, 0, $this->backgroundColor);

		//Set the image thickness
		imagesetthickness($this->chart, $this->lineWidth);

		//Then we calculate the corresponding values for Y axis grid and associated label.
		$y_grid_labels = Array();

		//we'll also need the max width of the grid labels. we'll use this value to calculate the correct Y axis position on the image.
		$label_y_max_width = 0;

		//We calculate each value according to how many grid lines i've been asked for.
		for ($i = 0; $i <= $this->gridLines_y; $i++)
			{
			//The value of this gridline is a linear division of the max value among the grid size.
			$value_y = ($maxValue / $this->gridLines_y) * ($this->gridLines_y - $i);

			//We calculate the corresponding labelbox width in order to draw the graph correctly.
			$labelBox = imagettfbbox($fontSizePt, 0, $fontFile, number_format($value_y, $this->label_y_decimals, ",", "."));
			$labelWidth = $labelBox[4] - $labelBox[0];

			//Then the value and the label are stored for future processing.
			$y_grid_labels[] = ["label" => number_format($value_y, $this->label_y_decimals, ",", "."), "width" => $labelWidth];

			//If this was the label with larger width, we keep the value.
			if ($labelWidth > $label_y_max_width)
				{
				$label_y_max_width = $labelWidth;
				}
			}

		//The we start drawing the Y axis. The start and finish of these lines are constant, so we use a couple of variables.
		$x1 = $this->margin + $label_y_max_width + $this->margin;
		$x2 = $this->width - $this->margin;

		//We calculate the start of the axys in Y. The upper point must have the margin plus half the height of a label.
		$y1 = $this->margin + ($this->fontSize / 2);

		//The lower point must consider the starting point up to the Y axis grid height.
		$y2 = $y1 + $gridHeight;

		//And then we draw the first line, the Y axis itself.
		imageline($this->chart, $x1, $y1, $x1, $y2, $this->gridColor);

		//Then we can start drawing the Y grid lines.
		for ($i = 0; $i <= $this->gridLines_y; $i++)
			{
			//we must calculate the Y value for this grid line. Linear scale is used.
			$y_barStartingPosition = $this->margin + ($this->fontSize / 2) + ($gridHeight / $this->gridLines_y) * $i;

			//And we calculate the value for the corresponding label.
			$value_y = ($maxValue / $this->gridLines_y) * ($this->gridLines_y - $i);

			//Line draw.
			imageline($this->chart, $x1, $y_barStartingPosition, $x2, $y_barStartingPosition, $this->gridColor);

			//Label positioning. must be aligned to right, so we calculate using the max label width
			$labelX = $this->margin + ($label_y_max_width - $y_grid_labels[$i]["width"]);

			//Y position is easier. we just halve the font height.
			$labelY = $y_barStartingPosition + $this->fontSize / 2;

			//Then we draw the label.
			imagettftext($this->chart, $fontSizePt, 0, $labelX, $labelY, $this->labelColor, $fontFile, $y_grid_labels[$i]["label"]);
			}

		//Now its time to process X values. The X axis length is easy calculated.
		$gridWidth = $x2 - $x1;

		//Then we calculate the position for the first value of data.
		$x_offset = $this->margin + $label_y_max_width + $this->margin + $this->margin;

		//And how much px we need to move forward for each data element.
		$x_step_size = $gridWidth / (count($dataValues));

		//The y starting position for each data element
		$y_barStartingPosition = $this->margin + ($this->fontSize / 2) + $gridHeight;

		//The bar width must be calculated according to the margin.
		$barWidth = $x_step_size - ($this->margin * 2);

		//Max position of the bar to fit in the graph
		$y_upper_bound = $this->margin + ($this->fontSize / 2) - 1;

		//We itereate over the data.
		for ($i = 0; $i < count($dataValues); $i++)
			{
			//The X position is calculated according to the bar graph start point
			$x = $x_offset + ($i) * ($x_step_size);

			//The max Y height is calculated according to the real height of the bar graph and the data value.
			//Bear in mind that the Y value is specified from top-left corner for image purposes
			$max_y = $y_upper_bound + $gridHeight - ($gridHeight * ($dataValues[$i] / $maxValue)) +1;

			//Then we draw a rectangle to represent the data value.
			imagefilledrectangle($this->chart, $x, $max_y, $x + $barWidth, $y_barStartingPosition - $this->lineWidth, $this->barColor);

			//Then the label below the X axis.
			$labelBox = imagettfbbox($fontSizePt, 0, $fontFile, $dataLabels[$i]);

			//We calculate the labelbox width to center the text correctly below the data value bar.
			$labelWidth = $labelBox[4] - $labelBox[0];

			//Points for text center.
			$labelX = $x + ($barWidth / 2) - ($labelWidth / 2);
			$labelY = $this->height - $this->margin;

			//And draw the text label.
			imagettftext($this->chart, $fontSizePt, 0, $labelX, $labelY, $this->labelColor, $fontFile, $dataLabels[$i]);
			}

		//To get the image output we need to use output buffering. we start it to avoid echoing to stdout.
		ob_start();

		//Create a PNG with the graphics we've just made
		imagepng($this->chart);

		//Store all the output in a variable
		$graph = ob_get_clean();

		if ($exportAsHtmlImage)
			{
			//If we've been asked to return an HTML img tag, we add some magic.
			$base64data = base64_encode($graph);
			return "<img src='data:image/png;base64,$base64data' />";
			}
		else
			{
			//Otherwise we just return the data.
			return $graph;
			}
		}

	/**
	 * Sets how many decimals are used for the Y axis labels values.
	 * @param type $decimals
	 */
	public function setGridLinesLabelsDecimals($decimals)
		{
		$this->label_y_decimals = $decimals;
		}

	/**
	 * Sets the margin in px between the elements in the graph
	 * @param type $margin
	 */
	public function setMarginSize($margin)
		{
		$this->margin = $margin;
		}

	/**
	 * Sets the data for the bar graph
	 * @param Array $graphData bar graph data in the form of ["x_label" => "y_value"]
	 */
	public function setData($graphData)
		{
		$this->graphData = $graphData;
		}

	/**
	 * Sets the bar color
	 */
	public function setBarColor($r, $g, $b)
		{
		$this->barColor = imagecolorallocate($this->chart, $r, $g, $b);
		}

	/**
	 * Sets the axis line color
	 */
	public function setAxisColor($r, $g, $b)
		{
		$this->axisColor = imagecolorallocate($this->chart, $r, $g, $b);
		}

	/**
	 * Sets the axis grid line color
	 */
	public function setGridColor($r, $g, $b)
		{
		$this->gridColor = imagecolorallocate($this->chart, $r, $g, $b);
		}

	/**
	 * Sets the text label color
	 */
	public function setLabelsColor($r, $g, $b)
		{
		$this->labelColor = imagecolorallocate($this->chart, $r, $g, $b);
		}

	/**
	 * Sets the background color
	 */
	public function setBackgroundColor($r, $g, $b)
		{
		$this->backgroundColor = imagecolorallocate($this->chart, $r, $g, $b);
		}

	/**
	 * Set how many grid lines on Y axis must be generated
	 * @param type $y_gridLines
	 */
	public function setYGridLines($y_gridLines)
		{
		$this->gridLines_y = $y_gridLines;
		}

	}