<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/

//{{{ iFile
/**
 * Interface declares required methods for file objects.
 */
interface iFile extends iFileSystemObject{

	/**
	 * Returns URL to access to the object.
	 *
	 * @return string
	 */
	public function getUrl();

	/**
	 * Returns size of the file.
	 *
	 * @return string
	 */
	public function size();

	/**
	 * Copying file to the target object.
	 *
	 * $target object can represents file or directory.
	 *
	 * @param  iFileSystemObject The target object.
	 * @return $iFile Instance of the copied object.
	 */
	public function copy(iFileSystemObject $target);

	/**
	 * Depends of the value of $newName method renames or moves object.
	 *
	 * @param mixed $newName Renames/moves target.!!
	 *
	 * @retrun iFile Instance of the renamed/moved object.
	 */
	public function rename($newName);

	/**
	 * Moves object.
	 *
	 * Depends of the $target type moves object in different manner.
	 *
	 * @param mixed $target Target path/name or object.
	 * @return iFile Instance of the moved object.
	 */ 
	public function move($target);

	/**
	 * Deletes object from file system.
	 *
	 * @return bool Returns true if the object was deleted successfully,
	 *				otherwise returns false.
	 */
    public function delete();
}
//}}}

