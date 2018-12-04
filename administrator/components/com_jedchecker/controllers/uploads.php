<?php
/**
 * @author     Daniel Dimitrov <daniel@compojoom.com>
 * @date       26.10.15
 *
 * @copyright  Copyright (C) 2008 - 2015 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');

/**
 * Class JedcheckerControllerUploads
 *
 * @since  1.0
 */
class JedcheckerControllerUploads extends JControllerlegacy
{
	/**
	 * Constructor.
	 *
	 */
	public function __construct()
	{
		$this->path         = JFactory::getConfig()->get('tmp_path') . '/jed_checker';
		$this->pathArchive  = $this->path . '/archives';
		$this->pathUnzipped = $this->path . '/unzipped';
		parent::__construct();
	}

	/**
	 * basic upload
	 *
	 * @return bool
	 */
	public function upload()
	{
		$appl  = JFactory::getApplication();
		$input = JFactory::getApplication()->input;
				
		// Check the sent token by the form
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Gets the uploaded file from the sent form
		$file = $input->files->get('extension', null, 'raw');
		
		if ( ($file['tmp_name']) && ($file["type"] == "application/x-zip-compressed")  )
		{
			$path = $this->pathArchive;

			// If the archive folder doesn't exist - create it!
			if (!JFolder::exists($path))
			{
				JFolder::create($path);
			}
			else
			{
				// Let us remove all previous uploads
				$archiveFiles = JFolder::files($path);

				foreach ($archiveFiles as $archive)
				{
					if (!JFile::delete($this->pathArchive . '/' . $archive))
					{
						echo 'could not delete' . $archive;
					}
				}
			}

			$filepath = $path . '/' . strtolower($file['name']);


			$object_file           = new JObject($file);
			$object_file->filepath = $filepath;
			$file                  = (array) $object_file;
			
			// Let us try to upload
			if (!JFile::upload($file['tmp_name'], $file['filepath'], false, true))
			{
				// Error in upload - redirect back with an error notice
				JFactory::getApplication()->enqueueMessage(JText::_('COM_JEDCHECKER_ERROR_UNABLE_TO_UPLOAD_FILE'), 'error');
				$appl->redirect('index.php?option=com_jedchecker&view=uploads');

				return false;
			}
			
			// Unzip uploaded files
			$unzip_result = $this->unzip();
			
			//$appl->redirect('index.php?option=com_jedchecker');

			$appl->redirect('index.php?option=com_jedchecker', JText::_($unzip_result));
			

			return true;
		} else {
			JFactory::getApplication()->redirect('index.php?option=com_jedchecker', JText::_('Invalid file type'),'error');
			//JFactory::getApplication()->enqueueMessage(JText::_('Invalid file type'), 'error');
			//$this->setRedirect('index.php?option=com_jedchecker&view=uploads');
		}

		return false;
	}

	/**
	 * unzip the file
	 *
	 * @return bool
	 */
	public function unzip()
	{
		$appl  = JFactory::getApplication();
		
		// Form check token
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// If folder doesn't exist - create it!
		if (!JFolder::exists($this->pathUnzipped))
		{
			JFolder::create($this->pathUnzipped);
		}
		else
		{
			// Let us remove all previous unzipped files
			$folders = JFolder::folders($this->pathUnzipped);

			foreach ($folders as $folder)
			{
				JFolder::delete($this->pathUnzipped . '/' . $folder);
			}
		}

		$file   = JFolder::files($this->pathArchive);
		$result = JArchive::extract($this->pathArchive . '/' . $file[0], $this->pathUnzipped . '/' . $file[0]);

		if ($result)
		{
			// Scan unzipped folders if we find zip file -> unzip them as well
			$this->unzipAll($this->pathUnzipped . '/' . $file[0]);
			$message = 'COM_JEDCHECKER_UNZIP_SUCCESS';	
			//dump("enetra","entra");
			JFactory::getApplication()->enqueueMessage(JText::_($message));			
		}
		else
		{
			$message = 'COM_JEDCHECKER_UNZIP_FAILED';			
		}
		
		//$appl->redirect('index.php?option=com_jedchecker&view=uploads', JText::_($message));

		return $message;
	}

	/**
	 * Recursively go through each folder and extract the archives
	 *
	 * @param   string $start - the directory where we start the unzipping from
	 *
	 * @return  void
	 */
	public function unzipAll($start)
	{
		$iterator = new RecursiveDirectoryIterator($start);

		foreach ($iterator as $file)
		{
			if ($file->isFile())
			{
				$extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

				if ($extension == 'zip')
				{
					$unzip  = $file->getPath() . '/' . $file->getBasename('.' . $extension);
					$result = JArchive::extract($file->getPathname(), $unzip);

					// Delete the archive once we extract it
					if ($result)
					{
						JFile::delete($file->getPathname());

						// Now check the new extracted folder for archive files
						$this->unzipAll($unzip);
					}
				}
			}
			elseif (!$iterator->isDot())
			{
				$this->unzipAll($file->getPathname());
			}
		}
	}
	
	/**
	 * clear tmp folders
	 *
	*/
	public function clear()
	{
		if ( file_exists($this->path) ) 
		{
			$result = JFolder::delete($this->path);	
			
			if (!$result)
			{
				echo 'could not delete ' . $this->path;
				$message = 'COM_JEDCHECKER_DELETE_FAILED'; 
			}
			
			$message = 'COM_JEDCHECKER_DELETE_SUCCESS'; 
			
			JFactory::getApplication()->redirect('index.php?option=com_jedchecker&view=uploads', JText::_($message));
			
		}		
	}
}
