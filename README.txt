Architecture Quick Start

The architecture operates on the following premise:

The existence of files is all that's required to configure a site. That is, if a file exists in the appropriate folder such as pages (say, home.html), then the file can be accessed by a URL as below.

 http://domain.com/?home

Similarly, a file in the pages folder within the css folder called home.css will be loaded along with the home.html page. All CSS files are loaded in the order of specificity, which means base files are loaded first, then subdirectory files, then page specific files. The same is true for JavaScript files in the jsincludes or javascript folder.

If you create another directory within the pages directory similar subdirectories within css, jsincludes, javascript, library, and includes will also load in a similar manner. Any files in the main directories will be included in all files.

Another additional feature to provide even more flexibility, is the use of what are termed sub-sites. These are new directories in the main directory that also operate much as the main directories do, however, these are accessed from the URL by preceding the page name with the folder directory name followed by a colon as below.

http://domain.com/?site:home

There is an additional directory, the con directory which contains settings for various configurable values. Of note is the css.php file which contains values which can be set and then later used in CSS files to provide a means to use global values for colors, spacing, dimensions, and anything else.

The images directory is directly referenced, although files in sub-sites with the same name as those in the base image directory are used instead. The same is true for any matching files in the base directories. This allows you to override ANY content/settings and so on just by using a different URL, even if the main html page is not in the sub-site.

Looking at some of the files supplied in pages and parts should give you reasonable examples. If you have a questions, feel free to e-mail me.

There are quite a few libraries in the core directory. All files are loaded and the functionality is available to all pages. This core directory should be considered non-modifiable. If you want to add libraries, place them in the includes or library directory. You can create a pages directory in that as well to load some libraries on only some pages. Note that the entire set of core libraries is less than 0.5MB. The code is very efficient and I believe well documented.

Have fun.