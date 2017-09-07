## Requirements

Windows: Putty
MAC OS: terminal -> ssh root@....

## Set up theme

go to .../public_html

## drush to make new theme

Drush command <br> `drush new-theme theme_machine_name "theme_title" "theme_description"`

Drush command alias <br> `drush nt theme_machine_name "theme_title" "theme_description"`


## Set as default your theme

go to sitename.dev/admin/appearance

"<b>Set as default</b>" your theme



## How to use theme, gulp and libraries

first install libraries <br> `npm install`

now you have everything that you need in node_modules folder

### Gulp tasks

To run gulp in dev mod and start browsersync <br> `gulp`<br>

You don't need compile scss in production mode, because it automatically works.

Get images from dist/images folder optimise and copy to image folder  (this should be done one time when you add new images) <br> `gulp images`<br>

Generate breakpoints base on THEMENAME.breakpoint.yml file and put in scss/variables/_breakpoints.scss (this should be done one time if you change .yml file) <br> `gulp breakpoint`<br>

