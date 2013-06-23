newest


<?
/**==============================================
*	Basecamp Component
*	6/21/2013
*	 
*	 ___ ___    _   ___ __  __ ___ _  _ _____
*	| __| _ \  /_\ / __|  \/  | __| \| |_   _|
*	| _||   / / _ \ (_ | |\/| | _|| .` | | |
*	|_| |_|_\/_/ \_\___|_|  |_|___|_|\_| |_|
*							FRAGMENTLABS.COM
*	
*	Author: Josh Bielick
*	
*==============================================*/
App::uses('Component', 'Controller');
class BasecampComponent extends Component {
	
	var $appName;
	var $contactInfo;
	var $username;
	var $password;
	var $apiUrl;
	
	function initialize()
	{
		$this->appName = 'Fragment Summit';
		$this->contactInfo = 'http://fragmentlabs.com';
		$this->username = 'basecamp@fragmentlabs.com';
		$this->password = 'dGhyZWV2ZTM1';
		$this->apiUrl = 'https://basecamp.com/1759232/api/v1';
	}
	
	function get($endPoint, $query = array())
	{
		return $this->request('GET', $endPoint, $query);
	}
	
	function post($endPoint, $data = array())
	{
		$headers = array('Content-Type: application/json; charset=utf-8');
		return $this->request('POST', $endPoint, $data);
	}
	
	function delete($endPoint, $query = array())
	{
		return $this->request('DELETE', $endPoint, $query);
	}
	
	function put($endPoint, $data = array())
	{
		return $this->request('PUT', $endPoint, $data);
	}
	
	function request($method, $endPoint, $params = array(), $headers = array())
	{
		$url = $this->apiUrl.$endPoint.'.json';
		if(in_array($method, array('GET', 'DELETE')))
		{
			$url .= '?'.http_build_query($params);
		}
		$headers[] = 'User-Agent: '.$this->appName.' ('.$this->contactInfo.')';
		if(in_array($method, array('POST', 'PUT'))) {
			$data = stripslashes(json_encode($params));
		} else {
			$data = array();
		}
		
		$c = curl_init();
		$this->curl_opts($c, $method, $url, $data, $headers);
		$response = curl_exec($c);
		curl_close($c);
		
		list($response_headers, $response_body) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
		$headers = $this->parse_headers($response_headers);
		if(!in_array($headers['status'], array(404, 403, 500)))
			return json_decode($response_body);
		else
			return false;
	}
	
	private function parse_headers( $header ) {
		// $retVal = array();
		// $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
		// foreach( $fields as $field ) {
		// 	if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
		// 		$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
		// 		if( isset($retVal[$match[1]]) ) {
		// 			if ( is_array( $retVal[$match[1]] ) ) {
		// 				$i = count($retVal[$match[1]]);
		// 				$retVal[$match[1]][$i] = $match[2];
		// 			}
		// 			else {
		// 				$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
		// 			}
		// 		} else {
		// 			$retVal[$match[1]] = trim($match[2]);
		// 		}
		// 	}
		// }
		// return $retVal;
	}
	
	private function curl_opts($c, $method, $url, $data, $headers)
	{
		if(in_array($method, array('POST', 'PUT'))) {
			curl_setopt($c, CURLOPT_POST, 1);
		}
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_USERPWD, $this->username.':'.base64_decode($this->password));
		curl_setopt($c, CURLOPT_MAXREDIRS, 3);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	}

	/**==============================================
	*	Accesses
	*==============================================*/
	/**
	 * Lists all the people with access to the specified project or calendar
	 *
	 * @param string $projectId 
	 * @return array list of users
	 * @link https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#get-accesses
	 * @author Josh
	 */
	function listAccess($projectId, $type='projects')
	{
		$endPoint = '/'.$type.'/'.$projectId.'/accesses';
		$response = $this->get($endPoint);
		return $response;
	}
	
	//https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#grant-access
	function addAccess($grantees, $invite=false) //need to specify calendar/project
	{
		$people = array();
		if(is_array($grantees) && !empty($grantees)) {
			if($invite)
				$people['email_addresses'] = $grantees;
			else
				$people['ids'] = $grantees;
			//not finished
		}
	}
	
	//https://github.com/37signals/bcx-api/blob/master/sections/accesses.md#revoke-access
	function deleteAccess($id)
	{
		//not finished
		//DELETE /projects/1/accesses/1.json will revoke the access of the person who's id is mentioned in the URL. (Same goes for calendars with /calendars/ instead)
	}

	/**==============================================
	*	Attachments
	*==============================================*/
	/**
	 * Lists all attachments
	 *
	 * @param string $projectId Set to retrieve all attachments by project, leave empty to retrieve all attachments by account.
	 * @param string $page Results are paginated—limit 50. First page is default.
	 * @return array Array of attachments
	 * @link https://github.com/37signals/bcx-api/blob/master/sections/attachments.md#get-attachments
	 * @author Josh
	 */
	function listAttachment($projectId, $page=null)
	{
		$endPoint = (isset($projectId) && !empty($projectId)) ? '/projects/'.$projectId.'/attachments' : 'attachments';
		$params = ($page) ? array('page'=>$page) : array();
		return $this->get($endPoint, $params);
	}
	
	function addAttachment($data)
	{
		//POST /attachments.json uploads a file. The request body should be the binary data of the attachment. Make sure to set the Content-Type and Content-Length headers.
	}

	/**==============================================
	*	Calendar Events
	*==============================================*/
	/**
	 * Lists Calendar Events
	 *
	 * @param string $id Id of the project or calendar
	 * @param string $idType default is 'projects', set to 'calendars' for calendar for a specific calendar
	 * @param boolean $past boolean return events in the past.
	 * @return array Array of calendar events for project or calendar specified
	 * @author Josh
	 */
	function listCalendarEvent($type = 'projects', $id, $past=false)
	{
		$endPoint = '/'.$type.'/'.$id.'/calendar_events';
		if($past)
			$endPoint .= '/past';
		return $this->get($endPoint);
	}
	
	function viewCalendarEvent($type = 'projects', $eventId, $parentId)
	{
		$endPoint = '/'.$type.'/'.$parentId.'/calendar_events/'.$eventId;
		return $this->get($endPoint);
	}
	
	function addCalendarEvent($data)
	{
		#  https://github.com/37signals/bcx-api/blob/master/sections/calendar_events.md#create-calendar-event
	}
	
	function editCalendarEvent($id, $data)
	{
		#  https://github.com/37signals/bcx-api/blob/master/sections/calendar_events.md#create-calendar-event
	}
	
	function deleteCalendarEvent($id, $parentId, $parentIdType='projects')
	{
		$endPoint = '/'.$type.'/'.$id.'/calendar_events/'.$id;
		return $this->delete($endPoint);
	}

	/**==============================================
	*	Comments
	*==============================================*/
	function addComment($data)
	{
		# https://github.com/37signals/bcx-api/blob/master/sections/comments.md#create-comment
	}
	
	function attachToComment()
	{
		#  https://github.com/37signals/bcx-api/blob/master/sections/comments.md#attaching-files
	}
	
	function deleteComment($id, $projectId)
	{
		$endPoint = '/projects/'.$projectId.'/comments/'.$id;
		return $this->delete($endPoint);
	}
	
	/**==============================================
	*	Documents
	*==============================================*/
	//

	/**==============================================
	*	Events
	*==============================================*/	
	/**
	 * See all global events for the currently authorized account.
	 *
	 * @param time $since Unix ISO8601 Timestamp
	 * @param string|integer $page Page number to show (if pagination)
	 * @return array Array of all events after 'since' timestamp
	 * @author Josh
	 */
	function listEvent($since, $page)
	{
		if(!isset($since))
			throw new Exception('Required argument not provided (timestamp) "since".');
		$query = array('since' => $since);
		if(isset($page))
			$query['page'] = $page;
		$endPoint = '/events';
		return $this->get($endPoint, $query);
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
	function viewEvent($type = 'projects', $id, $since, $page)
	{
		if(!isset($since))
			throw new Exception('Required argument not provided (timestamp) "since".');
		$query = array('since' => $since);
		if(isset($page))
			$query['page'] = $page;
		$endPoint = '/'.$type.'/'.$id.'/events';
		return $this->get($endPoint, $query);
	}

	/**==============================================
	*	Message
	*==============================================*/
	function viewMessage($projectId, $id)
	{
		$endPoint = '/projects/'.$projectId.'/messages/'.$id;
		return $this->get($endPoint);
	}
	
	function addMessage($data)
	{
		# https://github.com/37signals/bcx-api/blob/master/sections/messages.md
	}
	
	function attachToMessage()
	{
		
	}
	
	function editMessage($projectId, $id, $data)
	{
		# use PUT
	}
	
	function deleteMessage($projectId, $id)
	{
		$endPoint = '/projects/'.$projectId.'/messages/'.$id;
		return $this->delete($endPoint);
	}

	/**==============================================
	*	People
	*==============================================*/
	function listPerson()
	{
		return $this->get('/people');
	}
	
	function viewPerson($id)
	{
		return $this->get('/people/'.$id);
	}
	
	function me() {
		return $this->get('/people/me');
	}
	
	function todosPerson($id)
	{
		return $this->get('/people/'.$id.'/assigned_todos');
	}

	/**==============================================
	*	Projects
	*==============================================*/
	function readProject($id)
	{
		$this->data = $this->get('/projects/'.$id);
		return $this;
	}
	
	/**
	 * Will return all active projects.
	 *
	 * @param boolean $archived set to true to show archived projects.
	 * @return array Array of Projects
	 * @author Josh
	 */
	function listProject($archived=false)
	{
		$endPoint = ($archived) ? '/projects/archived' : '/projects';
		return $this->get($endPoint);
	}
	
	/**
	 * View a project instance
	 *
	 * @param string $id 
	 * @return array Array of the project data
	 * @author Josh
	 */
	function viewProject($id)
	{
		return $this->get('/projects/'.$id);
	}
	
	/**
	 * Creates a new project with the data provided
	 *
	 * @param array $data Array of project data to be inserted
	 * @return array|boolean Returns array of new project instance or false on failure.
	 * @author Josh
	 */
	function addProject($data)
	{
		return $this->post('/projects', $data);
	}
	
	/**
	 * Edit a project
	 *
	 * @param string $id Project ID
	 * @param array $data Array of data to update
	 * @return array|boolean Instance of project if success, false on failure
	 * @author Josh
	 */
	function editProject($id, $data)
	{
		return $this->put('/projects/'.$id, $data);
	}
	
	/**
	 * Archive a project
	 *
	 * @param string $id 
	 * @return boolean Success
	 * @author Josh
	 */
	function archiveProject($id)
	{
		return $this->put('/projects/'.$id, array('archived'=>true));
	}
	
	/**
	 * Unarchive a project.
	 *
	 * @param string $id 
	 * @return boolean Success
	 * @author Josh
	 */
	function unArchiveProject($id)
	{
		return $this->put('/projects/'.$id, array('archived'=>false));
	}
	
	/**
	 * Delete a person
	 *
	 * @param string $id Id of the person to delete.
	 * @return boolean Success.
	 * @author Josh
	 */
	function deleteProject($id)
	{
		return $this->delete('/people/'.$id);
	}
	
	/**==============================================
	*	Todo Lists
	*==============================================*/
	
	/**
	 * shows active todolists for this project (or all) sorted by position.
	 *
	 * @param string $projectId Project ID.
	 * @param boolean $completed Show completed lists or not.
	 * @return array Array of todo lists by Project (if ID supplied) or active todo lists for all projects.
	 * @link https://github.com/37signals/bcx-api/blob/master/sections/todolists.md
	 * @author Josh
	 */
	function listTodoList($projectId = null, $completed = false)
	{
		$endPoint = ($completed) ? '/todolists/completed' : '/todolists';
		if($projectId)
			$endPoint = '/projects/'.$projectId.$endPoint;
		return $this->get($endPoint);
	}
	
	function readTodoList($projectId, $id)
	{
		$this->data = $this->get('/projects/'.$projectId.'/todolists/'.$id);
		return $this;
	}
	
	/**
	 * will return the specified todolist including the todos.
	 *
	 * @param string $projectId Project ID.
	 * @param string $id Todo list ID.
	 * @return array the todolist with todos sorted by position.
	 * @author Josh
	 */
	function viewTodoList($projectId, $id)
	{
		return $this->get('/projects/'.$projectId.'/todolists/'.$id);
	}
	
	/**
	 * will create a new todolist from the parameters passed in $data
	 *
	 * @param string $projectId Project ID.
	 * @param array $data Array of data to be inserted.
	 * @return array|boolean the todolist instance or false on failure.
	 * @author Josh
	 */
	function addTodoList($projectId, $data)
	{
		return $this->post('/projects/'.$projectId.'/todolists', $data);
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
	function editTodoList($projectId, $id, $data)
	{
		return $this->put('/projects/'.$projectId.'/todolists/'.$id, $data);
	}
	
	function positionTodoList($position)
	{
		// return $this->put('')
	}
	
	function deleteTodoList($projectId, $id)
	{
		return $this->delete('/projects/'.$projectId.'/todolists/'.$id);
	}
	
	/**==============================================
	*	Todos
	*==============================================*/
	function viewTodo($projectId, $id)
	{
		return $this->get('/projects/'.$projectId.'/todos/'.$id);
	}
	
	function addTodo($projectId, $todolistId, $data)
	{
		return $this->post('/projects/'.$projectId.'/todolists/'.$todolistId.'/todos', $data);
	}
	
	function editTodo($projectId, $id, $data)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, $data);
	}
	
	function completeTodo($projectId, $id)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, array('completed'=>true));
	}
	
	function unCompleteTodo($projectId, $id)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, array('completed'=>false));
	}
	
	function assignTodo($projectId, $id, $personId)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, array('assignee'=>array('id'=>$personId,'type'=>'Person')));
	}
	
	function unAssignTodo($projectId, $id)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, array('assignee'=>null));
	}
	
	function positionTodo($projectId, $id, $position)
	{
		return $this->put('/projects/'.$projectId.'/todos/'.$id, array('position'=>$position));
	}
	
	function deleteTodo($projectId, $id)
	{
		return $this->delete('/project/'.$projectId.'/todoes/'.$id);
	}

	/**==============================================
	*	Topics
	*==============================================*/
	function listTopic($projectId=null, $page=null)
	{
		$endPoint = ($projectId) ? '/projects/'.$projectId.'/topics' : '/topics';
		$query = ($page) ? array('page'=>$page) : null;
		return $this->get($endPoint);
	}

	/**==============================================
	*	Uploads
	*==============================================*/
	function addUpload($projectId, $data)
	{
		return $this->post('/projects/'.$projectId.'/uploads', $data);
	}
}




