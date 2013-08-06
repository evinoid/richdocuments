<?php

/**
 * @license
 * Copyright (C) 2013 KO GmbH <copyright@kogmbh.com>
 *
 * @licstart
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Affero General Public License
 * (GNU AGPL) as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.  The code is distributed
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU AGPL for more details.
 *
 * As additional permission under GNU AGPL version 3 section 7, you
 * may distribute non-source (e.g., minimized or compacted) forms of
 * that code without the copy of the GNU GPL normally required by
 * section 4, provided you include this license notice and a URL
 * through which recipients can access the Corresponding Source.
 *
 * As a special exception to the AGPL, any HTML file which merely makes function
 * calls to this code, and for that purpose includes it by reference shall be
 * deemed a separate work for copyright law purposes. In addition, the copyright
 * holders of this code give you permission to combine this code with free
 * software libraries that are released under the GNU LGPL. You may copy and
 * distribute such a system following the terms of the GNU AGPL for this code
 * and the LGPL for the libraries. If you modify this code, you may extend this
 * exception to your version of the code, but you are not obligated to do so.
 * If you do not wish to do so, delete this exception statement from your
 * version.
 *
 * This license applies to this entire compilation.
 * @licend
 * @source: http://www.webodf.org/
 * @source: http://gitorious.org/webodf/webodf/
 */
// OCP\JSON::checkLoggedIn();
// OCP\JSON::checkAppEnabled('office');
// session_write_close();

function bogusSession($i){
	$bs = array();
	$bs["denomination"] = "[$i] bogus session";
	$bs["id"] = "$i";
	$bs["title"] = "bogus.odt";
	$bs["mimetype"] = "application/vnd.oasis.opendocument.text";
	$bs["members"] = $bs["cursors"] = array("bob_10002", "alice_10001");
	$bs["creation_date"] = "2013-07-16T18:52:50.120Z";
	return $bs;
}

try{
	$request = new OCA\Office\Request();
	$command = $request->getParam('command');
	$response = array();
	switch ($command){
		case 'session-list':
			$response["session_list"] = array(bogusSession(0), bogusSession(1));
			break;
		case 'join-session':
			$response = "true"; // should fail when session is non-existent
			break;
		case 'sync-ops':
			$seqHead = $request->getParam('args/seq_head');

			if (!is_null($seqHead)){
				$esId = $request->getParam('args/es_id');
				$memberId = $request->getParam('args/member_id');
				$ops = $request->getParam('args/client_ops');

				$currentHead = OCA\Office\Op::getHeadSeq($esId);

				//if $postobject['args']['seq_head'] is the most recent op in the ops-table:
				// append all ops in $postobject['args']['client_ops'] to the ops-table
				if ($seqHead > $currentHead){
					foreach ($ops as $op){
						$op['opspec'] = json_encode($op['opspec']);
						try{
							OCA\Office\Op::add($op);
						} catch (Exception $e){
							
						}
					}
				} else {
					//     result: 'conflict',
					//     ops: a list of all ops since  $postobject['args']['seq_head']
					//     headSeq: the most recent op-seq
					$response["result"] = 'conflict';
					$response["ops"] = OCA\Office\Op::getOpsAfter($esId, $seqHead);
					$last = end($response["ops"]);
					$response["headSeq"] = $last['seq'];
				}
			} else {
				// Error - empty seq_head passed :)
				throw new BadRequestException();
			}

			break;
		default:
			$ex = new BadRequestException();
			$ex->setBody("{err:'bad request: [" . $request->getRawRequest() . "]'}");
			throw $ex;
			break;
	}

	\OCP\JSON::success($response);
} catch (BadRequestException $e){
	header('HTTP/1.1 400: BAD REQUEST');
	print("");
	print($e->getBody());
	print("");
}
exit();
	
class BadRequestException extends Exception {

	protected $body = "";

	public function setBody($body){
		$this->body = $body;
	}

	public function getBody(){
		return $this->body;
	}
}