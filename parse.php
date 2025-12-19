<?php

require_once(dirname(__FILE__) . '/sqlite.php');

// min and max visitor numbers in NCBI taxonomy
$min_left = 1;
$max_right = 2803563;

//----------------------------------------------------------------------------------------
function get_edges($filename)
{
	global $min_left;
	global $max_right;

	$edges = array();
	
	$headings = array();
	
	$row_count = 0;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
			
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$row = fgetcsv(
			$file_handle, 
			0, 
			"\t" 
			);
			
		$go = is_array($row);
		
		if ($go)
		{
			if ($row_count == 0)
			{
				$headings = $row;		
			}
			else
			{
				$obj = new stdclass;
			
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$obj->{$headings[$k]} = $v;
					}
				}
			
				//print_r($obj);	
				
				// get leaf number
				$from = 0;
				$to = 0;
				
				$host_taxid = 0;
				$associate_taxid = 0;
				
				if (isset($obj->host))
				{
					$host_taxid = $obj->host;
				}
	
				if (isset($obj->associate))
				{
					$associate_taxid = $obj->associate;
				}
	
				if (isset($obj->parasite))
				{
					$associate_taxid = $obj->parasite;
				}
				
				if ($host_taxid !== 0 && $host_taxid !== 0)
				{
					$data = db_get('SELECT * FROM tree WHERE id=' . $associate_taxid);
					
					foreach ($data as $row)
					{
						$from = $row->left + ($row->right - $row->left)/ 2;
					}
		
					$data = db_get('SELECT * FROM tree WHERE id=' . $host_taxid);
					
					foreach ($data as $row)
					{
						$to = $row->left + ($row->right - $row->left)/ 2;
					}
					
					if ($from !== 0 && $to !== 0)
					{
						if (!isset($edges[$from]))
						{
							$edges[$from] = array();
						}
						
						if (!in_array($to, $edges[$from]))
						{
							$edges[$from][] = $to;
						}
					}
					
				}
				
			}
		}	
		$row_count++;
	}

	return $edges;
}

//----------------------------------------------------------------------------------------
function edges_to_diagram($edges)
{
	global $min_left;
	global $max_right;

	// angles
	
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
	<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns="http://www.w3.org/2000/svg" 
	width="1000px" height="1000px">';
	
	$pi = 3.14159265;
	$radius = 200;
	$cx = 500;
	$cy = 500;
	
	$outer_radius = 10;
	
	// circle representing tree of life
	$xml .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" stroke="black" stroke-width="1" fill="none"/>';
	
	// higher taxon arcs
	
	$arcs = array(
		6231  => "Nematoda",
		50557 => "Insecta",
		6157  => "Platyhelminthes",
		7742  => "Vertebrata",
		6657  => "Crustacea",
		4751  => "Fungi",
		33090 => "Viridiplantae",
		2 	  => "Bacteria",
		2157  => "Archaea",
	);
	
	// https://github.com/rdmpage/taxaprisma
	$arc_colours = array(
		"Nematoda" 			=> "#FF4500",
		"Insecta" 			=> "#FF4500",
		"Platyhelminthes" 	=> "#FF4500",
		"Vertebrata" 		=> "#1E90FF",
		"Crustacea" 		=> "#FF4500",
		"Fungi" 			=> "#F52887",
		"Viridiplantae"		=> "#73AC13",

		"Archaea"			=> "#FF00FF",
		"Bacteria"			=> "#0000FF",
		
	);
	
	foreach ($arcs as $tax_id => $name)
	{
		$data = db_get('SELECT * FROM tree WHERE id=' . $tax_id);
		
		foreach ($data as $row)
		{
			$left = $row->left;
			$right = $row->right;
			
			$r1 = ($left / $max_right) * 360;
			$r2 = ($right / $max_right) * 360;
			
			$x1 = cos(deg2rad($r1)) * ($radius + $outer_radius) + $cx;
			$y1 = sin(deg2rad($r1)) * ($radius + $outer_radius) + $cy;
		
			$x2 = cos(deg2rad($r2)) * ($radius + $outer_radius) + $cx;
			$y2 = sin(deg2rad($r2)) * ($radius + $outer_radius) + $cy;
			
			$color = 'red';
			
			$xml .= '<path d="M' . $x1 . ',' . $y1 . ' A210 210 1 0 1 ' . $x2 . ',' . $y2 . '"
				fill="none" stroke="' . $arc_colours[$name] . '" stroke-width="10" opacity="0.4" />';
	
		}
	
	
	}
	
	
	
	
	// example names
	
	$landmarks = array(
		9606 => "human",
		7215 => "Drosophila",
		6239 => "C. elegans",

		6668 => "Daphnia",
		5820 => "Plasmodium",
		6202 => "Taenia",
		
		4930 => "Saccharomyces",
		
		4022 => "Acer",
		
		562 => "E. coli",
	
	);
	
	
	foreach ($landmarks as $tax_id => $name)
	{
		$place = 0;
		
		$data = db_get('SELECT * FROM tree WHERE id=' . $tax_id);
		
		foreach ($data as $row)
		{
			$place = $row->left + ($row->right - $row->left)/ 2;
		}
		
		if ($place !== 0)
		{
			$r = ($place / $max_right) * 360;
			
			$x = cos(deg2rad($r)) * ($radius + $outer_radius) + $cx;
			$y = sin(deg2rad($r)) * ($radius + $outer_radius) + $cy;
	
			$xml .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . '6' . '" stroke="black"
			stroke-width="1" fill="white"/>';
	
			// need to think about placing labels
			if ($x > $cx)
			{			
				$xml .= '<text x="' . ($x + 20 ) . '" y="' . ($y+5) . '">' . $name . '</text>';		
			}
			else
			{			
				$xml .= '<text x="' . ($x - (strlen($name) * 9) ) . '" y="' . ($y+5) . '">' . $name . '</text>';		
			}
		
		}
	
	
	}
	
	
	
	
	// arcs connecting associations
	
	foreach ($edges as $from => $toList)
	{
		foreach ($toList as $to)
		{
			// calculate
			
			/*
			$x1 = cos(deg2rad($r1)) * $radius + $cx;
			$y1 = sin(deg2rad($r1)) * $radius + $cy;
		
		
			$x2 = cos(deg2rad($r2)) * $radius + $cx;
			$y2 = sin(deg2rad($r2)) * $radius + $cy;
			*/
			
			$r1 = ($from / $max_right) * 360;
			$r2 = ($to / $max_right) * 360;
			
			$x1 = cos(deg2rad($r1)) * $radius + $cx;
			$y1 = sin(deg2rad($r1)) * $radius + $cy;
		
			$x2 = cos(deg2rad($r2)) * $radius + $cx;
			$y2 = sin(deg2rad($r2)) * $radius + $cy;
			
			$xml .= '<path d="M' . $x1 . ',' . $y1 . ' Q' . $cx . ',' . $cy . '  ' . $x2 . ',' . $y2 . '" fill="none" stroke="black" stroke-width="4" style="opacity:0.1" />';
			
		
		}
	}
	
	$xml .= '	
		</svg>';
	
	return $xml;
}


$filename = '';
if ($argc < 2)
{
	echo "Usage: " . basename(__FILE__) . " <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$path_parts = pathinfo($filename);

$output_filename = $path_parts['filename'] . '.svg';

$edges = get_edges($filename);

$svg = edges_to_diagram($edges);

file_put_contents($output_filename, $svg);



?>
