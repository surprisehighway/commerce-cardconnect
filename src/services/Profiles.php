<?php
/**
 * CardConnect for Craft Commerce plugin for Craft CMS 3.x
 *
 * CardConnect integration for Craft Commerce 2
 *
 * @link      http://www.joel-king.com
 * @copyright Copyright (c) 2019 jmauzyk
 */

namespace jmauzyk\commerce\cardconnect\services;

use jmauzyk\commerce\cardconnect\errors\ProfileException;
use jmauzyk\commerce\cardconnect\models\Profile;
use jmauzyk\commerce\cardconnect\records\Profile as ProfileRecord;

use Craft;
use craft\db\Query;
use craft\elements\User;
use yii\base\Component;
use yii\base\Exception;

/**
 * Profiles service
 *
 * @author    jmauzyk
 * @package   CardConnect
 * @since     1.4.0
 *
 */
class Profiles extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a profile by gateway and user
     *
     * @param int $gatewayId The CardConnect gateway
     * @param User $user The user
     *
     * @return Profile
     */
    public function getProfile(int $gatewayId, User $user): ?Profile
    {
        $result = $this->_createProfileQuery()
            ->where(['userId' => $user->id, 'gatewayId' => $gatewayId])
            ->one();

        if ($result !== null) {
            return new Profile($result);
        }

        return null;
    }

    /**
     * Return a profile by its id.
     *
     * @param int $id
     *
     * @return Profile|null
     */
    public function getProfileById(int $id): ?Profile
    {
        $profileRow = $this->_createProfileQuery()
            ->where(['id' => $id])
            ->one();

        if ($profileRow) {
            return new Profile($profileRow);
        }

        return null;
    }

    /**
     * Return a profile by its reference.
     *
     * @param string $reference
     *
     * @return Profile|null
     */
    public function getProfileByReference(string $reference): ?Profile
    {
        $profileRow = $this->_createProfileQuery()
            ->where(['reference' => $reference])
            ->one();

        if ($profileRow) {
            return new Profile($profileRow);
        }

        return null;
    }

    /**
     * Save a profile
     *
     * @param Profile $profile The profile being saved.
     * @return bool Whether the payment source was saved successfully
     * @throws Exception if payment source not found by id.
     */
    public function saveProfile(Profile $profile): bool
    {
        if ($profile->id) {
            $record = ProfileRecord::findOne($profile->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce-cardconnect', 'No profile exists with the ID “{id}”',
                    ['id' => $profile->id]));
            }
        } else {
            $record = new ProfileRecord();
        }

        $record->userId = $profile->userId;
        $record->gatewayId = $profile->gatewayId;
        $record->reference = $profile->reference;

        $profile->validate();

        if (!$profile->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $profile->id = $record->id;

            return true;
        }

        return false;
    }

    /**
     * Delete a profile by it's id.
     *
     * @param int $id The id
     *
     * @return bool
     * @throws \Throwable in case something went wrong when deleting.
     */
    public function deleteProfileById($id): bool
    {
        $record = ProfileRecord::findOne($id);

        if ($record) {
            return (bool)$record->delete();
        }

        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving profiles.
     *
     * @return Query The query object.
     */
    private function _createProfileQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'userId',
                'gatewayId',
                'reference'
            ])
            ->from(['{{%cardconnect_profiles}}']);
    }

}
