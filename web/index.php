<html>
<head>
<title>Bitcoin Script Analysis</title>
<style>

.bar {
  fill: steelblue;
}

.bar:hover {
  fill: brown;
}

.axis {
  font: 10px sans-serif;
}

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

.x.axis path {
  display: none;
}

.d3-tip {
  line-height: 1;
  font-weight: bold;
  padding: 12px;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  border-radius: 2px;
}

/* Creates a small triangle extender for the tooltip */
.d3-tip:after {
  box-sizing: border-box;
  display: inline;
  font-size: 10px;
  width: 100%;
  line-height: 1;
  color: rgba(0, 0, 0, 0.8);
  content: "\25BC";
  position: absolute;
  text-align: center;
}

/* Style northward tooltips differently */
.d3-tip.n:after {
  margin: -1px 0 0 0;
  top: 100%;
  left: 0;
}

table, td{
 border: 2px solid;
}

body{
 background-color: #ececec;
}
</style>
</head>
<body>
<script src="http://d3js.org/d3.v3.min.js"></script>
<script src="http://labratrevenge.com/d3-tip/javascripts/d3.tip.v0.6.3.js"></script>
<h1>Bitcoin Script Analysis</h1>
<p>
This is an analysis of locking scripts used in bitcoin transactions. 
Below is a chart showing the top 10 uncommon bitcoin locking scripts that have been used in transactions. 
Clicking on a script's bar in the chart will take you to a blockchain.info page about a transaction containing such a script, and scrolling over a bar will show how much value in Satoshi has been transferred with that kind of script. It is interesting to see that frequent use of a script does not always mean that it has been used to transfer a lot of value in bitcoin. 
Below the chart is a complete table of aggregate data on uncommon transactions.
All of this data can be shown over a span of blocks of your choice (all data is shown by default; data collection started at block 150000).
The data is periodically updated by a python script which can be found <a href="https://github.com/SabaEskandarian/CryptocurrencyProject">here</a>.
 This project was inspired by the analysis QuantaBytes did a year ago, available at  <a href="http://www.quantabytes.com/articles/a-survey-of-bitcoin-transaction-types">this link</a>. 
It is hoped that this information will stay relevant over time since it will be regularly updated by the script. 
</p>
<p>
The following four transaction types are excluded from the information presented on this page because they are very common and including them would dwarf all other transaction types on the charts:
<ul>
<li>OP_DUP OP_HASH160 DATA_20 OP_EQUALVERIFY OP_CHECKSIG - most typical bitcoin transactoin, pay to public key</li>
<li>OP_HASH160 DATA_20 OP_EQUAL - pay to script hash</li>
<li>DATA_65 OP_CHECKSIG - giving newly mined bitcoin/fees to the miner</li>
<li>DATA_33 OP_CHECKSIG - giving newly mine bitcoin/fees to the miner</li>
</ul>
</p>
<p>
If you are interested in the overall aggregate amount of bitcoin transactions, the following two files give aggregate information of all bitcoin transactions included in the blockchain, sorted by transaction count and total transaction value, respectively:
<ul>
<li><a href="by_count.txt">Transactions by count</a> - This file sorts locking scripts by the number of times they have been used. Each line has a script, the number of times it has appeared, and the amount of bitcoin (in satoshi) that it has been used to lock, in that order. </li>
<li><a href="by_value.txt">Transactions by value</a> - This file sorts locking scripts by the amount of satoshi they have been used to lock. Each line has a script, the amount of bitcoin (in satoshi) that it has been used to lock, and the number of times it has appeared, in that order. </li>
</ul>
</p>


<?php
$file = fopen("data.csv", "w");
$db_handle = new SQLite3("data.db");
fwrite($file, "script,count,value,txhash\n");
$max = $db_handle->query("SELECT * from Max order by num desc limit 1")->fetchArray()[0];
$from=150000;
$to=$max;
if(isset($_GET['from']))
{
	$from=$_GET['from'];
}
if(isset($_GET['to']))
{
	$to=$_GET['to'];
}
$from_clean = SQLite3::escapeString($from);
$to_clean = SQLite3::escapeString($to);
$results = $db_handle->query("select script, count(*) as count, sum(value) as value, txhash from Special where block between ".$from_clean." and ".$to_clean." group by script order by count desc limit 10;");
while($row=$results->fetchArray())
{
	fwrite($file, $row[0].",".$row[1].",".$row[2].",".$row[3]."\n");
}

fclose($file);

print "<p>Data last updated at block  ".$max."</p>";
?>
<form action="index.php" method="GET">
start from block <input type="number" name="from" min="150000" value="<?php print $from ?>" /><br />
go to block <input type="number" name="to" min="150000" value="<?php print $to?>"/><br />
<input type="submit" />
</form>
<script>
var margin = {top: 40, right: 20, bottom: 400, left: 80},
    width = 960 - margin.left - margin.right,
    height = 800 - margin.top - margin.bottom;

var x = d3.scale.ordinal()
    .rangeRoundBands([0, width], .1);

var y = d3.scale.linear()
    .range([height, 0]);

var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left")

var tip = d3.tip()
  .attr('class', 'd3-tip')
  .offset([-10, 0])
  .html(function(d) {
    return "<strong>Value:</strong> <span style='color:red'>" + d.value + "</span>";
  })

var svg = d3.select("body").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

svg.call(tip);

d3.csv("data.csv", type, function(error, data) {
  x.domain(data.map(function(d) { return d.script; }));
  y.domain([0, d3.max(data, function(d) { return d.count; })]);

  svg.selectAll(".bar")
      .data(data)
    .enter().append("a")
      .attr("xlink:href", function(d){return "https://blockchain.info/tx/"+d.txhash;})
	.append("rect")
      .attr("class", "bar")
      .attr("x", function(d) { return x(d.script); })
      .attr("width", x.rangeBand())
      .attr("y", function(d) { return y(d.count); })
      .attr("height", function(d) { return height - y(d.count); })
      .on('mouseover', tip.show)
      .on('mouseout', tip.hide)

  svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis)
        .selectAll("text")  
            .style("text-anchor", "end")
            .attr("dx", "-.8em")
            .attr("dy", ".15em")
            .attr("transform", function(d) {
                return "rotate(-65)" 
                });

  svg.append("g")
      .attr("class", "y axis")
      .call(yAxis)
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", ".71em")
      .style("text-anchor", "end")
      .text("Count");


});

svg.append("text")
        .attr("x", (width / 2))             
        .attr("y", 0 - (margin.top / 2))
        .attr("text-anchor", "middle")  
        .style("font-size", "16px") 
        .style("text-decoration", "underline")  
        .text("Uncommon Transactions by Count");

function type(d) {
  d.count = +d.count;
  return d;
}

</script>

<h2>Table of Uncommon Transactions</h2>
<table>
<tr><th>Script</th><th>Count</th><th>Value</th></tr>
<?php
$by_count_result = $db_handle->query("select script, count(*) as count, sum(value) as value, txhash from Special where block between ".$from_clean." and ".$to_clean." group by script order by count desc;");
while($row=$by_count_result->fetchArray())
{
	print "<tr><td><a href='https://blockchain.info/tx/".$row[3]."'>".$row[0]."</a></td><td>".$row[1]."</td><td>".$row[2]."</td></tr>\n";
}

?>
</table>
</body>
</html>
