<?php
	function splat($str){
		$str=trim($str);
		if(empty($str)){
			return false;
		} else {
			$returnarray=preg_split("/[\r\n\t\,\;]+/", $str);
			$returnarray=trimarray($returnarray);
			return $returnarray;
		}
	}
	
	function trimarray($array){
		array_walk($array, 'trimit');
		return $array;
	}
	
	function trimit(&$value){
		$value = trim($value); 
	}
	
class FORUM{
	
	public $forum;
	public $user;
	public $userid;
	
	function __construct($id=NULL){
		if(!empty($id)){
			$this->user=get_auth_info('CHDS_login');
			$this->userid=AUTH::iduser($this->user);
			if($this->getForum($id)){
				return true;
			}
		}
		return false;
	}

	function getForum($id){
		$query='SELECT * FROM forum WHERE id=:id';
		if($results=DB::query("people",$query,array(':id'=>$id))){
			$permissions=splat($results[0]['permissions']);
			if(hasRole($this->user,$permissions)||empty($permissions)){
				$this->forum=$results[0];
				return true;
			}
		}
		return false;
	}
	
	function getForumFromCategory($id){
		$query='SELECT forumid FROM forum_category WHERE id=:id';
		if($results=DB::query("people",$query,array(':id'=>$id))){
			$this->getForum($results[0]['forumid']);
			return true;
		}
		return false;
	}
	
	function getForumFromPost($id){
		$query='SELECT categoryid FROM forum_post WHERE id=:id';
		if($results=DB::query("people",$query,array(':id'=>$id))){
			$this->getForumFromCategory($results[0]['categoryid']);
			return true;
		}
		return false;		
	}
	
	function addForum($data){
		if(isset($this->userid)){
			$data["createdby"]=isset($data["createdby"])?$data["createdby"]:$this->userid;
			$forumid=DB::insert('people','forum',$data);
			$this->getForum($forumid);	
			return $forumid;		
		}
		return false;
	}

	function updateForum($data){
		if(isset($this->userid)){
			$id=isset($data["id"])?$data["id"]:$this->forum['id'];
			unset($data["id"]);
			if(DB::update('people','forum',$data,array('id'=>$id))){
				$this->getForum($id);
				return $id;
			}	
		}
		return false;
	}
	
	function deleteForum($forumid){
		if(isset($this->userid)){
			$query="SELECT id FROM forum_category WHERE forumid=:forumid";
			if($results=DB::query('people',$query,array(':forumid'=>$forumid))){
				foreach($results as $result){
					self::deleteCategory($result["id"]);
				}
			}
			DB::update('people','forum', array('enabled'=>0), array('id'=>$forumid));
		}
		return false;
	}

	function addCategory($data){
		if(isset($this->userid)){
			$data["createdby"]=isset($data["createdby"])?$data["createdby"]:$this->userid;
			$data['forumid']=isset($data['forumid'])?$data['forumid']:$this->forum['id'];
			if(self::hasForumPermissions($data['forumid'])){
				$categoryid=DB::insert('people','forum_category', $data);
				self::updateCategoryOrder($categoryid, 0, $data['parentid']);
				return $categoryid;
			}
		}
		return false;
	}

	function updateCategory($data){
		if(isset($this->userid)){
			$id=$data["id"];
			unset($data["id"]);
			return DB::update('people','forum_category', $data, array('id'=>$id));
		}
		return false;
	}
	
	function deleteCategory($categoryid){
		if(isset($this->userid)){
			DB::update('people','forum_category', array('enabled'=>0), array('id'=>$categoryid));
			DB::update('people','forum_post', array('enabled'=>0), array('categoryid'=>$categoryid));
			return true;			
		}
		return false;
	}

	function updateCategoryOrder($categoryid, $position, $parentid){
		if($position!=0){
			$query='SELECT id FROM forum_category WHERE parentid=:parentid AND forumid=:forumid';
			$results=DB::query('people',$query,array(':parentid'=>$parentid,':forumid'=>$this->forum['id']));
			$x=1;
			foreach($results as $result){
				if($x==$position){
					$ordArray[]=$categoryid;
				}
				$ordArray[]=$result['id'];
			}
			for($x=1;$x<count($ordArray)+1;$x++){
				DB::update('people','forum_category', array('ord'=>$x), array('id'=>$ordArray[$x]));
			}
		} else {
			$query='SELECT count(id) as count FROM forum_category WHERE parentid=:parentid AND forumid=:forumid';
			$results=DB::query('people',$query,array(':parentid'=>$parentid,':forumid'=>$this->forum['id']));
			$order=$results[0]['count'];
			DB::update('people','forum_category', array('ord'=>$order), array('id'=>$categoryid));
		}		
	}

	function addPost($data){
		if(isset($this->userid)){
			if(self::hasCategoryPermissions($data["categoryid"])){
				$data["createdby"]=isset($data["createdby"])?$data["createdby"]:$this->userid;
				return DB::insert('people','forum_post', $data);
			}
		}
		return false;
	}
	
	function updatePost($data){
		if(isset($this->userid)){
			$postid=$data["id"];
			unset($data["id"]);
			return DB::update('people','forum_post', $data, array('id'=>$postid));
		}
		return false;
	}
	
	function deletePost($postid){
		if(isset($this->userid)){
			DB::update('people','forum_post', array('enabled'=>0), array('id'=>$postid));
			$query="SELECT id FROM forum_post WHERE topicid=:postid";
			$results=DB::query('people',$query,array(':postid'=>$postid));
			if($results){
				foreach($results as $result){
					self::deletePost($result["id"]);
				}
			}
		}
	}

	function getCategoryTopicCount($categoryid){
		$query="SELECT count(id) AS count FROM forum_post WHERE categoryid=:categoryid AND parentid IS NULL AND enabled=1";
		$results=DB::query('people',$query,array(':categoryid'=>$categoryid));
		if($results){
			return $results[0]["count"];
		} else {
			return 0;
		}
	}
	
	function getCategoryPostCount($categoryid){
		$query="SELECT count(id) AS count FROM forum_post WHERE categoryid=:categoryid AND parentid IS NOT NULL AND enabled=1";
		$results=DB::query('people',$query,array(':categoryid'=>$categoryid));
		if($results){
			return $results[0]["count"];
		} else {
			return 0;
		}	
	}
	
	function getTopicPostCount($topicid, &$postcount=0){
		$query="SELECT count(id) as count FROM forum_post WHERE topicid=:topicid AND enabled=1";
		$results=DB::query('people',$query,array(':topicid'=>$topicid));
		if($results){
			return $results[0]["count"];
		} else {
			return 0;
		}	
	}

	function listForums(){
		if(isset($this->user)){
			$query='SELECT * FROM forum';
			$results=DB::query('people',$query);
			$forums=array();
			foreach($results as $result){
				$permissions=splat($result['permissions']);
				if(hasRole($this->user,$permissions)||empty($permissions)){
					$forums[]=$result;
				}
			}
			return $forums;
		}
		return false;
	}

	function listCategories($forumid=NULL){
		if(isset($this->user)){
			$forumid=!empty($forumid)?$forumid:$this->forum['id'];
			if(self::hasForumPermissions($forumid)){
				$query='SELECT * FROM forum_category WHERE enabled=1 AND parentid IS NULL AND forumid=:forumid ORDER BY ord';
				if($results=DB::query('people',$query,array(':forumid'=>$forumid))){
					$categories=array();
					foreach($results as $result){
						$permissions=splat($result['permissions']);
						if(hasRole($this->user,$permissions)||empty($permissions)){
							$query='SELECT * FROM forum_category WHERE enabled=1 AND parentid=:parentid ORDER BY ord';
							$subresults=DB::query('people',$query,array(':parentid'=>$result['id']));
							if($subresults){
								foreach($subresults as $subresult){
									$permissions=splat($subresult['permissions']);
									if(hasRole($this->user,$permissions)||empty($permissions)){
										$result['subcategories'][]=$subresult;
									}
								}
							}
							$categories[]=$result;
						}
					}
					return $categories;
				}
			}
		}
		return false;
	}
	
	function listTopics($categoryid, $offset=0, $limit=NULL){
		$query='SELECT * FROM forum_post WHERE';
		$query.=$this->forum['allowflags']?' (flaggable=0 OR offensiveflagcount<3) AND':'';
		$query.=' enabled=1 AND categoryid=:categoryid AND topicid IS NULL ORDER BY sticky DESC, date DESC';
		$query.=$limit?' LIMIT '.$limit:'';
		$query.=" OFFSET ".$offset;
		return DB::query('people',$query,array(':categoryid'=>$categoryid));
	}
	
	function listPosts($postid, &$posts=NULL, $depth=0){
		if(!$posts){
			$posts=array();
		}
		$query='SELECT * FROM forum_post WHERE';
		$query.=$this->forum['allowflags']?' (flaggable=0 OR offensiveflagcount<3) AND':'';
		$query.=' enabled=1 AND parentid=:postid ORDER BY date DESC';	
		if($results=DB::query('people',$query,array(':postid'=>$postid))){
			foreach($results as $result){
				$result["depth"]=$depth;
				$posts[]=$result;
				$this->listPosts($result["id"], $posts, $depth+1);				
			}
		}
		return $posts;
	}
	
	function getPost($postid){
		$query='SELECT * FROM forum_post WHERE id=:postid';
		if($results=DB::query('people',$query,array(':postid'=>$postid))){
			return $results[0];
		}
		return false;
	}
	
	function getPostFiles($postid){
		$query='SELECT * FROM forum_files WHERE postid=:postid';
		if($results=DB::query('people',$query,array(':postid'=>$postid))){
			return $results;
		}
		return false;
	}
	
	function addPostFile($data){
		if(isset($this->userid)){
			$filename="/var/www/html/resources/forums/".$data["postid"]."/".$data["file"];
			if(@file_exists($filename)){
				unlink($filename);
			}
			if(!move_uploaded_file($data['tmp_name'], $filename)) {
		    	return false;
			}
			unset($data['tmp_name']);
			return DB::insert('people','forum_files', $data);
		}
		return false;
	}
	
	function deletePostFile($id){
		$query='SELECT * FROM forum_files WHERE id=:id';
		if($results=DB::query('people',$query,array(':id'=>$id))){
			$file=$results[0];
			$filename="/var/www/html/resources/forums/".$file["postid"]."/".$file["filename"];	
			if(@file_exists($filename)){
				unlink($filename);
			}
			DB::delete('people','forum_files',array("id"=>$id));			
		}
		return false;
	}
	
	function updatePostFile($data){
		$fileid=$data["id"];
		unset($data["id"]);
		return DB::update('people','forum_files', $data, array('id'=>$fileid));
	}
	
	function getCategory($categoryid){
		$query='SELECT * FROM forum_category WHERE id=:categoryid';
		if($results=DB::query('people',$query,array(':categoryid'=>$categoryid))){
			return $results[0];
		}
		return false;
	}
	
	function getLastPostDate($topicid){
		$query='SELECT date FROM forum_post WHERE topicid=:topicid LIMIT 1';
		if($results=DB::query('people',$query,array(':topicid'=>$topicid))){
			return $results[0]["date"];
		}
		return false;
	}
	
	function hasForumPermissions($forumid){
		$query="SELECT permissions FROM forum WHERE id=:forumid";
		if($results=DB::query('people',$query,array(':forumid'=>$forumid))){
			$permissions=splat($results[0]["permissions"]);
			if(!empty($permissions)){
				return hasRole($this->user,$permissions);
			}	
		}
		return true;
	}
	
	function hasCategoryPermissions($categoryid){
		$query="SELECT permissions FROM forum_category WHERE id=:categoryid";
		if($results=DB::query('people',$query,array(':categoryid'=>$categoryid))){
			$permissions=splat($results[0]["permissions"]);
			if(!empty($permissions)){
				return hasRole($this->user,$permissions);
			}
		}
		return true;
	}
	
	function navigationBar($url, $totalNumber, $numPerPage, $offset){
		$navbar="<div class='navbar'>";
		if($offset==0){
			$navbar.="<span class='nav disabled'><img src='images/begin_disabled.gif' height='15' width='16' alt='begin'/></span>";
			$navbar.="<span class='nav disabled'><img src='images/back_disabled.gif' height='15' width='14' alt='begin'/></span>";
		} else {
			$navbar.="<a href='" . $url . "&offset=0' class='nav'><img src='images/begin_off.gif' height='15' width='16' alt='begin'/></a>";
			$navbar.="<a href='" . $url . "&offset=" . ($offset-1) . "' class='nav'><img src='images/back_off.gif' height='15' width='16' alt='begin'/></a>";
		}
		$last=ceil($totalNumber/$numPerPage)-1;
		if(($offset+5)>$last){
			$end=$last;
			$start=$last-9;
		} else {
			$start=$offset-4;
			$end=$offset+5;
		}
		$start=$start<0?0:$start;
		for($x=$start;$x<$end+1;$x++){
			if($x==$offset){
				$navbar.="<span class='nav disabled'>".($x+1)."</span>";
			} else {
				$navbar.="<a href='" . $url . "&offset=" . $x . "' class='nav'>".($x+1)."</a>";
			}
		}
		if($totalNumber==0){
			$navbar.="<span class='nav disabled'>".($x+1)."</span>";
		}
		if($offset==$last||$totalNumber==0){
			$navbar.="<span class='nav disabled'><img src='images/forward_disabled.gif' height='15' width='14' alt='begin'/></span>";
			$navbar.="<span class='nav disabled'><img src='images/end_disabled.gif' height='15' width='16' alt='begin'/></span>";
		} else {
			$navbar.="<a href='" . $url . "&offset=" . ($offset+1) . "' class='nav'><img src='images/forward_off.gif' height='15' width='16' alt='begin'/></a>";
			$navbar.="<a href='" . $url . "&offset=" . $last . "' class='nav'><img src='images/end_off.gif' height='15' width='16' alt='begin'/></a>";
		}
		$navbar.="<div class='clear'></div></div>";
		echo $navbar;	
	}	
}
?>