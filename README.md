# Visualising host-parasite relationships using the NCBI taxonomy

Code to implement a simple visualisation of the “symbiome”, that is, associations between organisms and their hosts, as descibed in the iPhylo blog post “Visualising the symbiome: hosts, parasites, and the Tree of Life” [doi:https://doi.org/10.59350/54f0v-n7k97](https://doi.org/10.59350/54f0v-n7k97).

Idea is to take a tree of the NCBI taxonomy and arrange the leaves in a circle. If we have a pair of species with NCBI `tax_ids` (i.e., they have both been sequenced), then we can locate them on the circumference of this circle and connect them by a line. Below is a diagram of the human “symbiome” from the original blog post.

![human](human.png)

The code in this repository is very crude. The PHP script `parse.php` takes a TSV file comprising two columns: `associate` and `host`. These columns contain NCBI `tax_id` values for, say, a parasites and hosts. The script looks up the NCBI `taxi_id` in a SQLite database of the NCBI taxonomy, locates each taxon on the circumference of the circle, and draws the connecting line. Once all lines are drawn, the result is output as a SVG file.

