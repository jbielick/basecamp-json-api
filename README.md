# basecamp-json-api
a basecamp JSON API wrapper in PHP

##Usage

```php
$project = $bc->Project->read(1234);
$todolists = $project->TodoList->list();
$todoList = $project->TodoList->read($todolists[0]['id']);
$todos = $todoList->Todo->list();
$todo = $todoList->Todo->read($todos[0]['id']);
$todo->Comment->add(array('content' => 'You must complete this'));
```
