<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

require_once 'vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    protected $token;

    protected $result;

    protected $baseId;

    protected $localTemp;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct($token)
    {
        $this->folders = new \Romby\Box\Services\Folders(new \Romby\Box\Http\Adapters\GuzzleHttpAdapter(new \GuzzleHttp\Client()));
        $this->files = new \Romby\Box\Services\Files(new \Romby\Box\Http\Adapters\GuzzleHttpAdapter(new \GuzzleHttp\Client()));

        $this->token = $token;
    }

    /**
     * @beforeScenario
     */
    public function createTemporaryDirectory()
    {
        $this->baseId = $this->folders->create($this->token, 'tmp_'.microtime(), 0)['id'];
        $this->localTemp = __DIR__.'/'.'tmp_'.microtime();

        mkdir($this->localTemp);
    }

    /**
     * @afterScenario
     */
    public function deleteTemporaryDirectory()
    {
        $this->folders->delete($this->baseId, $this->token, [], true);

        $this->removeDir($this->localTemp);
    }

    /**
     * @When I create a folder with the name :name in the base directory
     * @Given I have a folder with the name :name in the base directory
     */
    public function iCreateAFolderWithTheNameInTheBaseDirectory($name)
    {
        $this->result = $this->folders->create($this->token, $name, $this->baseId);
    }

    /**
     * @Then the folder should be created
     */
    public function theFolderShouldBeCreated()
    {
        assertEquals('folder', $this->result['type']);
    }

    /**
     * @When I get information about the folder
     */
    public function iGetInformationAboutTheFolder()
    {
        $this->result = $this->folders->get($this->result['id'], $this->token);
    }

    /**
     * @Then I should receive information about a folder named :name in the base directory
     */
    public function iShouldReceiveInformationAboutAFolderNamedInTheBaseDirectory($name)
    {
        assertEquals($name, $this->result['name']);
        assertEquals($this->baseId, $this->result['parent']['id']);
    }

    /**
     * @When I set the folder's name to :name
     */
    public function iSetTheFolderSNameTo($name)
    {
        $this->folders->update($this->result['id'], $this->token, compact('name'));
    }

    /**
     * @When I copy that folder to the base directory with the name :name
     */
    public function iCopyThatFolderToTheBaseDirectoryWithTheName($name)
    {
        $this->result = $this->folders->copy($this->result['id'], $this->token, $name, $this->baseId);
    }


    /**
     * @When I create a shared link for that folder
     * @Given that folder has a shared link
     */
    public function iCreateASharedLinkForThatFolder()
    {
        $this->folders->createSharedLink($this->result['id'], $this->token, 'open');
    }

    /**
     * @Then the folder should have a shared link
     */
    public function theFolderShouldHaveASharedLink()
    {
        assertNotEmpty($this->result['shared_link']);
    }

    /**
     * @When I delete a shared link for that folder
     */
    public function iDeleteASharedLinkForThatFolder()
    {
        $this->folders->deleteSharedLink($this->result['id'], $this->token);
    }

    /**
     * @Then the folder should have no shared link
     */
    public function theFolderShouldHaveNoSharedLink()
    {
        assertEmpty($this->result['shared_link']);
    }

    /**
     * @Given I have a folder with the name :name in the trash
     */
    public function iHaveAFolderWithTheNameInTheTrash($name)
    {
        $this->result = $this->folders->create($this->token, $name, $this->baseId);
        $this->folders->delete($this->result['id'], $this->token);
    }

    /**
     * @When I get the contents of the trash
     */
    public function iGetTheContentsOfTheTrash()
    {
        $this->result = $this->folders->getTrash($this->token);
    }

    /**
     * @Then I should receive a list of items containing the folder :name
     */
    public function iShouldReceiveAListOfItemsContainingTheFolder($name)
    {
        assertContains($name, array_column($this->result['entries'], 'name'));
    }

    /**
     * @When I delete that folder permanently
     */
    public function iDeleteThatFolderPermanently()
    {
        $this->folders->deleteTrashed($this->result['id'], $this->token);
    }

    /**
     * @Then I should receive a list of items not containing the folder :name
     */
    public function iShouldReceiveAListOfItemsNotContainingTheFolder($name)
    {
        assertNotContains($name, array_column($this->result['entries'], 'name'));
    }


    /**
     * @When I restore that folder to the base directory as :name
     */
    public function iRestoreThatFolderToTheBaseDirectory($name)
    {
        $this->folders->restoreTrashed($this->result['id'], $this->token, $name, $this->baseId);
    }


    /**
     * @Given I have a folder named :name in that directory
     */
    public function iHaveAFolderNamedInThatDirectory($name)
    {
        $this->folders->create($this->token, $name, $this->result['id']);
    }

    /**
     * @When I get the items in the folder
     */
    public function iGetTheItemsInTheFolder()
    {
        $this->result = $this->folders->getItems($this->result['id'], $this->token);
    }

    /**
     * @Given I have a local file named :name
     */
    public function iHaveALocalFileNamed($name)
    {
        file_put_contents($this->localTemp.'/'.$name, 'content');
    }

    /**
     * @When I upload the file named :name
     */
    public function iUploadTheFileNamed($name)
    {
        $this->result = $this->files->upload($this->token, $this->localTemp.'/'.$name, $name, $this->baseId);
    }

    /**
     * @Then the file should be uploaded
     */
    public function theFileShouldBeUploaded()
    {
        assertEquals(1, $this->result['total_count']);
    }

    protected function removeDir($dir)
    {
        if (is_dir($dir))
        {
            $objects = scandir($dir);

            foreach ($objects as $object)
            {
                if ($object != "." && $object != "..")
                {
                    if (filetype($dir."/".$object) == "dir")
                    {
                        $this->removeDir($dir."/".$object);
                    }
                    else
                    {
                        unlink($dir."/".$object);
                    }
               }
            }

            reset($objects);

            rmdir($dir);
        }
    }

    /**
     * @Given I have a remote file named :name in the base directory
     */
    public function iHaveARemoteFileNamedInTheBaseDirectory($name)
    {
        $this->iHaveALocalFileNamed($name);
        $this->iUploadTheFileNamed($name);
    }

    /**
     * @When I get information about the file
     */
    public function iGetInformationAboutTheFile()
    {
        $this->result = $this->files->get($this->result['entries'][0]['id'], $this->token);
    }

    /**
     * @Then I should receive information about a file named :name in the base directory
     */
    public function iShouldReceiveInformationAboutAFileNamedInTheBaseDirectory($name)
    {
        assertEquals($name, $this->result['name']);
        assertEquals($this->baseId, $this->result['parent']['id']);
    }

    /**
     * @When I set the file's name to :name
     */
    public function iSetTheFileSNameTo($name)
    {
        $this->files->update($this->result['entries'][0]['id'], $this->token, compact('name'));
    }


    /**
     * @When I lock the file
     * @Given the file is locked
     */
    public function iLockTheFile()
    {
        //$this->files->lock($this->result['entries'][0]['id'], $this->token);
    }

    /**
     * @Then the file should be locked
     */
    public function theFileShouldBeLocked()
    {
        // Don't know how to check this!
    }

    /**
     * @Then the file should be unlocked
     */
    public function theFileShouldBeUnlocked()
    {
        // Don't know how to check this!
    }

    /**
     * @When I unlock the file
     */
    public function iUnlockTheFile()
    {
        //$this->files->unlock($this->result['entries'][0]['id'], $this->token);
    }
}
