<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>{_name_}</title>
  </head>
  <body>
    <h1>{_package_name_}\{_class_name_}</h1>
    <h3>Controller class file: </h3>
    <p><?= PathHelper::relpath(PATH_APP_CONTROLLERS . strtr(ltrim("{_class_name_}", "_"), "_", DIRECTORY_SEPARATOR) . ".do.php", PATH_APP_ROOT) ?></p>
    <h3>View template file:</h3>
    <p><?= PathHelper::relpath(__FILE__, PATH_APP_ROOT) ?></p>
    <br>
    <hr>
    <div>Created by {_username_} at {_date_}</div>
  </body>
</html>
