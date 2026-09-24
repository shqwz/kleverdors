<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  User.profileimage
 *
 * @copyright   (C) 2024 Custom Developer
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

 namespace Joomla\Plugin\User\Profileimage\Extension;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\Component;
use Joomla\CMS\Version;

$joomlaVersion = defined('JVERSION') ? JVERSION : (new Version())->getShortVersion();

if (version_compare($joomlaVersion, '6.0', '>=')) {
    if (!class_exists('Joomla\CMS\Filesystem\File')) {
        class_alias('\Joomla\Filesystem\File', 'Joomla\CMS\Filesystem\File');
    }
    if (!class_exists('Joomla\CMS\Filesystem\Folder')) {
        class_alias('\Joomla\Filesystem\Folder', 'Joomla\CMS\Filesystem\Folder');
    }
}

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseAwareTrait;
use RuntimeException;


/**
 * Profile Image Plugin
 *
 * @since  1.0.0
 */
final class Profileimage extends CMSPlugin
{
    use DatabaseAwareTrait;

    /**
     * Store uploaded files.
     *
     * @var array
     * @since 1.0.0
     */
    protected static $uploadedFiles = array();

    /**
     * Last profile image name.
     *
     * @var string
     */
    protected static $lastProfileImage = '';

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     *
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Runs on content preparation
     *
     * @param   string  $context  The context for the data
     * @param   object  $data     An object containing the data for the form.
     *
     * @return  boolean
     *
     * @since  1.0.0
     */
    public function onContentPrepareData($context, $data)
    {
        // Check we are manipulating a valid form.
        if (!in_array($context, ['com_users.profile', 'com_users.user', 'com_users.registration'])) {
            return true;
        }

        if (is_object($data)) {
            $userId = $data->id ?? 0;

            if (!isset($data->profileimage) && $userId > 0) {
                // Load the profile image data from the database.
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select(
                        [
                            $db->quoteName('profile_key'),
                            $db->quoteName('profile_value'),
                        ]
                    )
                    ->from($db->quoteName('#__user_profiles'))
                                    ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                ->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote('profileimage.%'))
                ->order($db->quoteName('ordering'));

                $db->setQuery($query);
                $results = $db->loadRowList();

                // Merge the profile image data.
                $data->profileimage = [];

                foreach ($results as $v) {
                    $k = str_replace('profileimage.', '', $v[0]);
                    $profileValue = json_decode($v[1], true);
                    
                    if ($profileValue === null) {
                        $profileValue = $v[1];
                    }
                    
                    $data->profileimage[$k] = $profileValue;
                }
            }
        }

        return true;
    }

    /**
     * Adds additional fields to the user editing form
     *
     * @param   Form   $form  The form to be altered.
     * @param   mixed  $data  The associated data for the form.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        // ENTRY POINT - This message should appear when plugin is loaded
        
        // Check we are manipulating a valid form.
        $name = $form->getName();

        if (!in_array($name, ['com_users.user', 'com_users.profile', 'com_users.registration'])) {
            return true;
        }

        // Add the profile image fields to the form.
        FormHelper::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');
        
        // Add the custom field path so Joomla can find our ProfileImage field type
        FormHelper::addFieldPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/profileimage');
        
        // Debug: Show what data we have

        $form->loadFile('profileimage', true);

        return true;
    }

    /**
     * Check file type
     *
     * @param   array   $file      The uploaded file data
     * @param   array   $allowed   Allowed file extensions
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected static function fileExtensionCheck($file, $allowed)
    {
        $ext = pathinfo($file['profileimage']['profile_image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            return true;
        }
        return false;
    }

    /**
     * Method is called before user data is stored in the database
     *
     * @param   array    $user   Holds the old user data.
     * @param   boolean  $isnew  True if a new user is stored.
     * @param   array    $data   Holds the new user data.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function onUserBeforeSave($user, $isnew, $data)
    {
        // Import filesystem libraries
        $input = Factory::getApplication()->input;
        self::$uploadedFiles = $input->files->get('jform');

        // Get last uploaded profile image
        if (!empty(self::existProfileImage($data['id']))) {
            $profileData = json_decode(self::existProfileImage($data['id'])->profile_value, true);
            if (is_array($profileData) && isset($profileData['profile_image'])) {
                self::$lastProfileImage = $profileData['profile_image'];
            } else {
                self::$lastProfileImage = self::existProfileImage($data['id'])->profile_value;
            }
        }

        // New profile image
        $imageName = self::$uploadedFiles['profileimage']['profile_image']['name'] ?? '';
        $allowedMediaExtensions = ComponentHelper::getComponent('com_media')->params->get('image_extensions');
        $allowedMediaExtensions = array_map('trim', explode(',', $allowedMediaExtensions));

        if (isset($imageName) && $imageName != '') {
            // Check file extension
            if (!self::fileExtensionCheck(self::$uploadedFiles, $allowedMediaExtensions)) {
                throw new RuntimeException(Text::_('PLG_USER_PROFILEIMAGE_ERROR_INVALID_EXTENSION'), 1);
            }
            // Check file size (2MB default)
            $maxSize = $this->params->get('max_file_size', 2097152);
            if (self::$uploadedFiles['profileimage']['profile_image']['size'] > $maxSize) {
                throw new RuntimeException(Text::_('PLG_USER_PROFILEIMAGE_ERROR_FILE_TOO_LARGE'), 1);
            }
        }

        return true;
    }

    /**
     * Saves user profile image data
     *
     * @param   array    $data    entered user data
     * @param   boolean  $isNew   true if this is a new user
     * @param   boolean  $result  true if saving the user worked
     * @param   string   $error   error message
     *
     * @return  void
     */
    public function onUserAfterSave($data, $isNew, $result, $error): void
    {
        $userId = ArrayHelper::getValue($data, 'id', 0, 'int');
        $imageName = self::$uploadedFiles['profileimage']['profile_image']['name'] ?? '';

        // Debug: Show when this method is called

        // Has new image
        if (isset($imageName) && $imageName != '') {
            $folderPath = JPATH_ROOT . '/' . $this->params->get('upload_path', 'images/profiles');
            if (!Folder::exists($folderPath)) {
                Folder::create($folderPath);
            }

            // Clean the filename
            $filename = File::makeSafe($imageName);
            $src = self::$uploadedFiles['profileimage']['profile_image']['tmp_name'];
        }

        if (($userId && $result && isset($data['profileimage']) && count($data['profileimage'])) || isset($src)) {
            // Delete old profile image if exists
            if (isset($imageName) && $imageName != '') {
                $existImage = '';
                if (!empty(self::existProfileImage($userId))) {
                    $profileData = json_decode(self::existProfileImage($userId)->profile_value, true);
                    if (is_array($profileData) && isset($profileData['profile_image'])) {
                        $existImage = $profileData['profile_image'];
                    } else {
                        $existImage = self::existProfileImage($userId)->profile_value;
                    }
                }

                if (isset($existImage) && $existImage != '') {
                    File::delete(JPATH_ROOT . $existImage);
                }
            }

            $db = Factory::getDbo();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = ' . $userId)
                ->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote('profileimage.%'));
            $db->setQuery($query);
            $db->execute();

            if (isset($imageName) && $imageName != '') {
                $filePath = '/' . $this->params->get('upload_path', 'images/profiles') . '/' . $userId . '-' . $filename;

                // If file upload then insert into DB
                if (File::upload($src, $folderPath . '/' . $userId . '-' . $filename)) {
                    $data['profileimage']['profile_image'] = $filePath;
                }
            }

            $query->clear()
                ->select($db->quoteName('ordering'))
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = ' . $userId);
            $db->setQuery($query);
            $usedOrdering = $db->loadColumn();

            $order = 1;
            $query->clear()
                ->insert($db->quoteName('#__user_profiles'));

            foreach ($data['profileimage'] as $k => $v) {
                while (in_array($order, $usedOrdering)) {
                    $order++;
                }

                $query->values(implode(',', [$userId, $db->quote('profileimage.' . $k), $db->quote(json_encode($v)), $order++]));
            }

            $db->setQuery($query);
            $db->execute();
        }
    }

    /**
     * Check if profile image exists
     *
     * @param   int     $userId  The user ID
     *
     * @return  object|null
     *
     * @since   1.0.0
     */
    protected static function existProfileImage($userId)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('profile_value')
            ->from($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = ' . $db->quote($userId))
            ->where($db->quoteName('profile_key') . ' = ' . $db->quote('profileimage.profile_image'));
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Remove all user profile image information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param   array    $user     Holds the user data
     * @param   boolean  $success  True if user was successfully stored in the database
     * @param   string   $msg      Message
     *
     * @return  void
     */
    public function onUserAfterDelete($user, $success, $msg): void
    {
        if (!$success) {
            return;
        }

        $userId = ArrayHelper::getValue($user, 'id', 0, 'int');

        if ($userId) {
            // Delete profile image file
            if (!empty(self::existProfileImage($userId))) {
                $profileData = json_decode(self::existProfileImage($userId)->profile_value, true);
                if (is_array($profileData) && isset($profileData['profile_image'])) {
                    $filePath = JPATH_ROOT . $profileData['profile_image'];
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                } else {
                    $filePath = JPATH_ROOT . self::existProfileImage($userId)->profile_value;
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
            }
            
            // Delete database records
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                ->where($db->quoteName('profile_key') . ' LIKE ' . $db->quote('profileimage.%'));

            $db->setQuery($query);
            $db->execute();
        }
    }
} 