<?php

require 'vendor/autoload.php';

use App\Charts\LineChart;
use App\Charts\BarChart;
use App\Charts\DoughnutChart;

$chart = new DoughnutChart();
$chart->series1Values = [12, 22];
$chart->labels = ["Aaoeuaoeuaoe", "B12oaeuaoeuao"];
$chart->createChart();
$chart->saveAs("Ring1.png");

$chart = new DoughnutChart();
$chart->series1Values = [12, 22];
$chart->labels = ["Aao", "B12"];
$chart->createChart();
$chart->saveAs("Ring2.png");

$chart = new DoughnutChart();
$chart->series1Values = [2561.0068, 2561.0068, 2444.5954, 2296.397, 5000, 3556.0847, 7110.1289];
$chart->labels = ['LT', 'GT', 'GG', 'PG', 'NB', 'VT', 'Unallocated'];
$chart->createChart();
$chart->saveAs("Ring3.png");

$chart = new LineChart();
$chart->series1Values = [25006.96, 25716.03, 25242.48, 26152.55, 27329.28, 27067.45, 32538.5, 34345.16, 36150.96, 34210.62, 37387.16, 37834.05, 39605.46, 31328.88, 31328.88];
$chart->labels = ['2021-01-01', '2021-02-01', '2021-03-01', '2021-04-01', '2021-05-01', '2021-06-01', '2021-07-01', '2021-08-01', '2021-09-01', '2021-10-01', '2021-11-01', '2021-12-01', '2022-01-01', '2022-02-01', '2022-02-05'];
$chart->title1 = "Value";
$chart->title1Labels = "Date";
$chart->createChart();
$chart->saveAs("Line1.png");

$chart = new LineChart();
$chart->series1Values = [12, 22];
$chart->labels = ["Aao", "B12"];
$chart->title1 = "AA";
$chart->title1Labels = "snauheontuhs";
$chart->createChart();
$chart->saveAs("Line2.png");


$chart = new BarChart();
$chart->series1Values = [25006.96, 25716.03, 25242.48, 26152.55, 27329.28, 27067.45, 32538.5, 34345.16, 36150.96, 34210.62, 37387.16, 37834.05, 39605.46, 31328.88, 31328.88];
$chart->labels = ['2021-01-01', '2021-02-01', '2021-03-01', '2021-04-01', '2021-05-01', '2021-06-01', '2021-07-01', '2021-08-01', '2021-09-01', '2021-10-01', '2021-11-01', '2021-12-01', '2022-01-01', '2022-02-01', '2022-02-05'];
$chart->title1 = "Performance";
$chart->title1Labels = "Date";
$chart->createChart();
$chart->saveAs("Bar1.png");

$chart = new BarChart();
$chart->series1Values = [25006.96, 39605.46, 31328.88];
$chart->labels = ['2021-01-01', '2022-01-01', '2022-02-05'];
$chart->title1 = "AA";
$chart->title1Labels = "snauheontuhs";
$chart->createChart();
$chart->saveAs("Bar2.png");

