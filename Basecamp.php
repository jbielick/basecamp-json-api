<?php

/**==============================================
* Basecamp JSON API Wrapper
*
* Author: Josh Bielick
*
* Usage
*   $bc = new Basecamp($config);
*
*   $project = $bc->Project->read(1234);
*   print_r($project);
*   $todolists = $project->TodoList->list();
*   $todoList = $project->TodoList->read($todolists[0]['id']);
*   $todos = $todoList->Todo->list();
*   $todo = $todoList->Todo->read($todos[0]['id']);
*   $todo->Comment->add(array('content' => 'You must complete this'));
*
*==============================================*/
class BCRequest {

  public static $requestUrl;
  public static $appName;
  public static $contactInfo;
  public static $username;
  public static $password;
  public static $accountId;
  public static $apiUrl;

  public static function get($endPoint, $query = array())
  {
    return self::request('GET', $endPoint, $query);
  }

  public static function post($endPoint, $data = array())
  {
    return self::request('POST', $endPoint, $data);
  }

  public static function delete($endPoint, $query = array())
  {
    return self::request('DELETE', $endPoint, $query);
  }

  public static function put($endPoint, $data = array())
  {
    return self::request('PUT', $endPoint, $data);
  }

  public static function request($method, $endPoint, $params = array(), $headers = array())
  {
    if(strpos(strtolower($endPoint), 'basecamp.com') === false) {
      $endPoint = self::$apiUrl.$endPoint.'.json';
    }
    if(in_array($method, array('GET', 'DELETE')) && !empty($params)) {
      $url .= '?'.http_build_query($params);
    }
    $headers[] = 'User-Agent: '.self::$appName.' ('.self::$contactInfo.')';

    self::$requestUrl = $endPoint;

    $c = curl_init();
    self::curl_opts($c, $method, $endPoint, $params, $headers);
    $response = curl_exec($c);
    curl_close($c);

    list($response_headers, $response_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
    // if(!in_array($headers['status'], array(404, 403, 500)))
    $response_headers = explode("\n", $response_headers);
    $headers = array();
    foreach($response_headers as $i => $header) {
      $header = explode(':', $header, 2);
      $headers[$header[0]] = trim($header[1]);
    }
    return json_decode($response_body);
  }

  private static function curl_opts($c, $method, $url, $data, $headers)
  {
    if($method != 'GET') {
      curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
    }
    if(in_array($method, array('POST', 'PUT'))) {
      $json = trim(stripslashes(json_encode($data)), '"');
      curl_setopt($c, CURLOPT_POSTFIELDS, $json);
      $headers[] = 'Content-Type: application/json; charset=utf-8';
      $headers[] = 'Content Length: '.strlen($json);
    }
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_USERPWD, self::$username.':'.self::$password);
    curl_setopt($c, CURLOPT_MAXREDIRS, 3);
    curl_setopt($c, CURLOPT_HEADER, true);
    curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
  }
}

class BCObject {

  protected $id;

  protected function store($data)
  {
    $this->requestUrl = BCRequest::$requestUrl;
    if($data) {
      foreach($data as $key => $value) {
        $this->$key = $value;
      }
    }
    return $this;
  }

  public function save()
  {
    $data = array();
    foreach(get_object_vars($this) as $key => $value) {
      if(!is_object($value) && !is_array($value)) {
        $data[$key] = $value;
      }
    }
    if(isset($this->id) && isset($this->requestUrl)) {
      return BCRequest::put($this->requestUrl, $data);
    } else {
      return $this->add(null, $this);
    }
  }

  public function _get($key)
  {
    return (isset($this->$key)) ? $this->$key : false;
  }

  public function _set($key, $value=null)
  {
    if(is_array($key) && !isset($value)) {
      foreach($key as $key1 => $value1) {
        $this->$key1 = $value1;
      }
    } else {
      $this->$key = $value;
    }
    return $this;
  }

  public function request() {
    return new BCRequest();
  }
}

class BCAccess extends BCObject {

  protected $id;
  protected $type;

  public function __construct($projectId=null, $type=null)
  {
    if($projectId) {
      $this->projectId = $projectId;
    }
    if($type) {
      $this->type = $type;
    }
  }

  /**
   * Lists all the people with access to the specified project
   *
   * @param string $projectId
   * @return array list of users
   * @link https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#get-accesses
   * @author Josh
   */
  public function list($projectId=null, $type='projects')
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    $endPoint = '/'.$type.'/'.$projectId.'/accesses';
    $response = BCRequest::get($endPoint);
    return $response;
  }

  //https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#grant-access
  public function add($grantees, $invite=false) //need to specify calendar/project
  {
    $people = array();
    if(is_array($grantees) && !empty($grantees)) {
      if($invite) {
        $people['email_addresses'] = $grantees;
      } else {
        $people['ids'] = $grantees;
      }
      //not finished
    }
  }

  //https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#revoke-access
  public function delete($id)
  {
    //not finished
    //DELETE /projects/1/accesses/1.json will revoke the access of the person who's id is mentioned in the URL. (Same goes for calendars with /calendars/ instead)
  }
}

class BCAttachment extends BCObject {

  /**
   * Lists all attachments
   *
   * @param string $projectId Set to retrieve all attachments by project, leave empty to retrieve all attachments by account.
   * @param string $page Results are paginated—limit 50. First page is default.
   * @return array Array of attachments
   * @link https://github.com/37signals/bcx-api/blob/master/sections/attachments.md#get-attachments
   * @author Josh
   */
  public function list($projectId, $page=null)
  {
    $endPoint = (isset($projectId) && !empty($projectId)) ? '/projects/'.$projectId.'/attachments' : 'attachments';
    $params = ($page) ? array('page'=>$page) : array();
    return BCRequest::get($endPoint, $params);
  }

  public function add($data)
  {
    //POST /attachments.json uploads a file. The request body should be the binary data of the attachment. Make sure to set the Content-Type and Content-Length headers.
  }
}

class BCCalendar extends BCObject {

  /**
   * Lists Calendar Events
   *
   * @param string $id Id of the project or calendar
   * @param string $idType default is 'projects', set to 'calendars' for calendar for a specific calendar
   * @param boolean $past boolean return events in the past.
   * @return array Array of calendar events for project or calendar specified
   * @author Josh
   */
  public function list($type = 'projects', $id, $past=false)
  {
    $endPoint = '/'.$type.'/'.$id.'/calendar_events';
    if($past) {
      $endPoint .= '/past';
    }
    return BCRequest::get($endPoint);
  }

  public function view($type = 'projects', $eventId, $parentId)
  {
    $endPoint = '/'.$type.'/'.$parentId.'/calendar_events/'.$eventId;
    return BCRequest::get($endPoint);
  }

  public function add($data)
  {
    #  https://github.com/37signals/bcx-api/blob/master/sections/calendar_events.md#create-calendar-event
  }

  public function edit($id, $data)
  {
    #  https://github.com/37signals/bcx-api/blob/master/sections/calendar_events.md#create-calendar-event
  }

  public function delete($id, $parentId, $parentIdType='projects')
  {
    $endPoint = '/'.$type.'/'.$id.'/calendar_events/'.$id;
    return BCRequest::delete($endPoint);
  }
}

class BCComment extends BCObject {

  var $projectId;
  var $item;
  var $itemId;

  public function __construct($item=null, $itemId=null, $projectId=null)
  {
    $this->item = $item;
    $this->itemId = $itemId;
    $this->projectId = $projectId;
  }

  public function add($data, $item=null, $itemId=null, $projectId=null)
  {
    $item = ($item) ? $item : $this->item;
    $itemId = ($itemId) ? $itemId : $this->itemId;
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::post('/projects/'.$projectId.'/'.$item.'/'.$itemId.'/comments', $data);
    # https://github.com/37signals/bcx-api/blob/master/sections/comments.md#create-comment
  }

  public function attach()
  {
    #  https://github.com/37signals/bcx-api/blob/master/sections/comments.md#attaching-files
  }

  public function delete($id, $projectId)
  {
    $endPoint = '/projects/'.$projectId.'/comments/'.$id;
    return BCRequest::delete($endPoint);
  }
}

class BCDocument extends BCObject {

}

class BCEvent extends BCObject {

  /**
   * See all global events for the currently authorized account.
   *
   * @param time $since Unix ISO8601 Timestamp
   * @param string|integer $page Page number to show (if pagination)
   * @return array Array of all events after 'since' timestamp
   * @author Josh
   */
  public function list($since, $page)
  {
    if(!isset($since)) {
      throw new Exception('Required argument not provided (timestamp) "since".');
    }
    $query = array('since' => $since);
    if(isset($page)) {
      $query['page'] = $page;
    }
    $endPoint = '/events';
    return BCRequest::get($endPoint, $query);
  }

  /**
   * View events by project or person.
   *
   * @param string $type 'projects' or 'people'
   * @param string $id The id of the project or person
   * @param string $since UNIX ISO8601 Timestamp of since threshold (required)
   * @param string $page Optional page number if paginated results (result limit is 50)
   * @return array Array of events
   * @author Josh
   */
  public function view($type = 'projects', $id, $since, $page)
  {
    if(!isset($since)) {
      throw new Exception('Required argument not provided (timestamp) "since".');
    }
    $query = array('since' => $since);
    if(isset($page)) {
      $query['page'] = $page;
    }
    $endPoint = '/'.$type.'/'.$id.'/events';
    return BCRequest::get($endPoint, $query);
  }
}

class BCMessage extends BCObject {

  public function get($projectId, $id)
  {
    $endPoint = '/projects/'.$projectId.'/messages/'.$id;
    return BCRequest::get($endPoint);
  }

  public function add($data)
  {
    # https://github.com/37signals/bcx-api/blob/master/sections/messages.md
  }

  public function attach()
  {

  }

  public function edit($projectId, $id, $data)
  {
    # use PUT
  }

  public function delete($projectId, $id)
  {
    $endPoint = '/projects/'.$projectId.'/messages/'.$id;
    return BCRequest::delete($endPoint);
  }
}

class BCPerson extends BCObject {

  public function list()
  {
    return BCRequest::get('/people');
  }

  public function view($id)
  {
    return BCRequest::get('/people/'.$id);
  }

  public function me() {
    return BCRequest::get('/people/me');
  }

  public function todos($id=null)
  {
    $id = ($id) ? $id : $this->id;
    return BCRequest::get('/people/'.$id.'/assigned_todos');
  }
}

class BCProject extends BCObject {

  /**
   * Will return all active projects.
   *
   * @param boolean $archived set to true to show archived projects.
   * @return array Array of Projects
   * @author Josh
   */
  public function list($archived=false)
  {
    $endPoint = ($archived) ? '/projects/archived' : '/projects';
    return BCRequest::get($endPoint);
  }

  /**
   * View a project instance
   *
   * @param string $id
   * @return array Array of the project data
   * @author Josh
   */
  public function view($id)
  {
    return BCRequest::get('/projects/'.$id);
  }

  /**
   * Creates a new project with the data provided
   *
   * @param array $data Array of project data to be inserted
   * @return array|boolean Returns array of new project instance or false on failure.
   * @author Josh
   */
  public function add($data)
  {
    return BCRequest::post('/projects', $data);
  }

  /**
   * Edit a project
   *
   * @param string $id Project ID
   * @param array $data Array of data to update
   * @return array|boolean Instance of project if success, false on failure
   * @author Josh
   */
  public function edit($id, $data)
  {
    return BCRequest::put('/projects/'.$id, $data);
  }

  /**
   * Archive a project
   *
   * @param string $id
   * @return boolean Success
   * @author Josh
   */
  public function archive($id)
  {
    return BCRequest::put('/projects/'.$id, array('archived'=>true));
  }

  /**
   * Unarchive a project.
   *
   * @param string $id
   * @return boolean Success
   * @author Josh
   */
  public function unArchive($id)
  {
    return BCRequest::put('/projects/'.$id, array('archived'=>false));
  }

  /**
   * Delete a person
   *
   * @param string $id Id of the person to delete.
   * @return boolean Success.
   * @author Josh
   */
  public function delete($id)
  {
    return BCRequest::delete('/projects/'.$id);
  }

  public function read($id)
  {
    parent::store($this->view($id));
    $this->TodoList = new BCTodoList($this->id);
    $this->Todo = new BCTodo(null, $this->id);
    $this->Access = new BCAccess($this->id, 'projects');
    return $this;
  }
}

class BCTodoList extends BCObject {

  protected $id;
  protected $projectId;

  public function __construct($projectId=null)
  {
    if($projectId) {
      $this->projectId = $projectId;
    }
  }

  /**
   * shows active todolists for this project (or all) sorted by position.
   *
   * @param string $projectId Project ID.
   * @param boolean $completed Show completed lists or not.
   * @return array Array of todo lists by Project (if ID supplied) or active todo lists for all projects.
   * @link https://github.com/37signals/bcx-api/blob/master/sections/todolists.md
   * @author Josh
   */
  public function list($projectId = null, $completed = false)
  {
    $endPoint = ($completed) ? '/todolists/completed' : '/todolists';
    $projectId = ($projectId) ? $projectId : $this->projectId;
    if($projectId) {
      $endPoint = '/projects/'.$projectId.$endPoint;
    }
    return BCRequest::get($endPoint);
  }

  /**
   * will return the specified todolist including the todos.
   *
   * @param string $projectId Project ID.
   * @param string $id Todo list ID.
   * @return array the todolist with todos sorted by position.
   * @author Josh
   */
  public function view($id, $projectId = null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::get('/projects/'.$projectId.'/todolists/'.$id);
  }

  /**
   * will create a new todolist from the parameters passed in $data
   *
   * @param string $projectId Project ID.
   * @param array $data Array of data to be inserted.
   * @return array|boolean the todolist instance or false on failure.
   * @author Josh
   */
  public function add($projectId, $data)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::post('/projects/'.$projectId.'/todolists', $data);
  }

  /**
   * will update the todolist from the parameters passed.
   *
   * @param string $projectId the Project ID.
   * @param string $id The Todolist ID.
   * @param array $data The data to be updated.
   * @return array|boolean Returns the instace of the todolist or false on failure.
   * @author Josh
   */
  public function edit($projectId, $id, $data)
  {
    return BCRequest::put('/projects/'.$projectId.'/todolists/'.$id, $data);
  }

  public function position($position)
  {
    // return BCRequest::put('')
  }

  public function delete($projectId, $id)
  {
    return BCRequest::delete('/projects/'.$projectId.'/todolists/'.$id);
  }

  public function read($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    if(!$this->projectId) {
      $this->projectId = $projectId;
    }
    $this->store($this->view($id, $projectId));
    $this->Todo = new BCTodo(null, $projectId);
    return $this;
  }
}

class BCTodo extends BCObject {

  protected $projectId;
  protected $id;

  public function __construct($id = null, $projectId=null)
  {
    if($projectId) {
      $this->projectId = $projectId;
    }
    if($id) {
      $this->id = $id;
    }
  }

  public function view($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::get('/projects/'.$projectId.'/todos/'.$id);
  }

  public function add($data, $id=null, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    $id = ($id) ? $id : $this->id;
    return BCRequest::post('/projects/'.$projectId.'/todolists/'.$todolistId.'/todos', $data);
  }

  public function edit($data, $id=null, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    $id = ($id) ? $id : $this->id;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, $data);
  }

  public function complete($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, array('completed'=>true));
  }

  public function unComplete($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, array('completed'=>false));
  }

  public function assign($personId, $id=null, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    $id = ($id) ? $id : $this->id;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, array('assignee'=>array('id'=>$personId,'type'=>'Person')));
  }

  public function unAssign($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, array('assignee'=>null));
  }

  public function position($projectId, $id, $position)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::put('/projects/'.$projectId.'/todos/'.$id, array('position'=>$position));
  }

  public function delete($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    return BCRequest::delete('/project/'.$projectId.'/todoes/'.$id);
  }

  public function read($id, $projectId=null)
  {
    $projectId = ($projectId) ? $projectId : $this->projectId;
    parent::store($this->view($id, $projectId));
    $this->Comment = new BCComment('todos', $this->id, $this->projectId);
    return $this;
  }
}

class BCTopic extends BCObject {

  protected $projectId;

  public function __construct($projectId=null)
  {
    if($projectId) {
      $this->projectId = $projectId;
    }
  }

  public function list($projectId=null, $page=null)
  {
    $endPoint = ($projectId) ? '/projects/'.$projectId.'/topics' : '/topics';
    $query = ($page) ? array('page'=>$page) : null;
    return BCRequest::get($endPoint);
  }
}

class BCUpload extends BCObject {

  public function add($projectId, $data)
  {
    return BCRequest::post('/projects/'.$projectId.'/uploads', $data);
  }
}



/**==============================================
* Class
*==============================================*/
class Basecamp {

  public $Access;
  public $Attachment;
  public $Calendar;
  public $Comment;
  public $Document;
  public $Event;
  public $Message;
  public $Person;
  public $Project;
  public $TodoList;
  public $Todo;
  public $Topic;
  public $data;

  public function __construct($config = array())
  {
    BCRequest::$appName = $config['appName'];
    BCRequest::$contactInfo = $config['contactInfo'];
    BCRequest::$username = $config['username'];
    BCRequest::$password = $config['password'];
    BCRequest::$accountId = $config['accountId'];
    BCRequest::$apiUrl = 'https://basecamp.com/'.BCRequest::$accountId.'/api/v1';

    $this->Access = new BCAccess();
    $this->Attachment = new BCAttachment();
    $this->Calendar = new BCCalendar();
    $this->Comment = new BCComment();
    $this->Document = new BCDocument();
    $this->Event = new BCEvent();
    $this->Message = new BCMessage();
    $this->Person = new BCPerson();
    $this->Project = new BCProject();
    $this->TodoList = new BCTodoList();
    $this->Todo = new BCTodo();
    $this->Topic = new BCTopic();
  }
}