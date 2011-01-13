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

//{{{ iDir
/**
 * Interface declares common methods for directory object.
 */
interface iDir extends iFileSystemObject{

	/**
	 * Factory method, returns iFile object(s) 
	 * by given $filename(s) located in the directory.
	 *
	 * @param mixed $filename String or array of string 
	 *		which represents file names in directory.
	 * @retrun mixed iFile object or array of iFile objects.
	 */
	public function getFile($filename);

	/**
	 * Returns iDir object which represents 
	 * sub-directory of this directory by given name.
	 *      
	 * @param $string The name of the sub-directory.
	 * @return iDir The sub-directory object.
	 */
	public function getDir($name);

	/**
	 * Returns list of files and directories.
	 *
	 * Function is processing glob pattern($pattern).
	 * Also functions can returns only files or only directories.
	 * Depending of implementation can process GLOB flags.
	 *
	 * @param string $pattern The 'glob' pattern.
	 * @param int	 $flag	  Ls specific flags.
	 * @param in	 $gflag	  'glob' flags.
	 * @return array Array of iFile or/and iDir objects.
	 */  	
	public function ls($pattern = null, $flag = Dir::LS_FILE, $gflag = 0);

	/**
	 * Creates new directory.
	 *
	 * See details in concrete implementations.
	 */
	public function mkdir();

	/**
	 * Function stores uploaded files in the directory.
	 *
	 * @see UploadedFiles
	 * @param UploadedFiles $uf Uploaded files representation.
	 * @return array Array of stored files.
	 */
	public function upload(UploadedFiles $uf);

	/**
	 * Function copies content of the  directory to another directory.
	 *
	 * If $createDirectory is set to true in taget directory
	 * will be copied whole directory(not only content).
	 *
	 * @param iDir $target			The target directory.
	 * @param bool $createDirectory 
	 * @retrun iDir The target directory.
	 */
	public function copy(iDir $target, $createDirectory = true);

	/**
	 * Moves directory.
	 *
	 * @param iDir The target directory.
	 * @retrun iDir Moved directory object.
	 */
	public function move(iDir $target);

	/**
	 * Renames directory.
	 *
	 * @param string New directory name.
	 * @return iDir Renamed direcory object.
	 */
	public function rename($newName);

	/**
	 * Deletes directory with all files and subdirectories.
	 * 
	 * @return bool Returns true if the directory was deleted successfully,
	 *				otherwise returns false.
	 */
    public function delete();
}// }}}

