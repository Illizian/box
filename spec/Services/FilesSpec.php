<?php

namespace spec\Romby\Box\Services;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Romby\Box\Http\HttpInterface;

class FilesSpec extends ObjectBehavior
{
    function let(HttpInterface $http)
    {
        $this->beConstructedWith($http);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Romby\Box\Services\Files');
    }

    function it_gets_information_about_files($http)
    {
        $http->get('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
        ])->willReturn('response');

        $this->get(0, 'foo')->shouldReturn('response');
    }

    function it_uploads_files($http)
    {
        $http->post('https://upload.box.com/api/2.0/files/content', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'body' => [
                'attributes' => json_encode(['name' => 'baz', 'parent' => ['id' => 0]]),
            ]
        ], 'bar')->willReturn('response');

        $this->upload('foo', 'bar', 'baz', 0)->shouldReturn('response');
    }

    function it_updates_information_about_a_file($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['bar' => 'baz']
        ])->willReturn('response');

        $this->update(0, 'foo', ['bar' => 'baz'])->shouldReturn('response');
    }

    function it_updates_information_about_a_file_only_if_it_knows_the_latest_version($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo', 'If-Match' => 'my-version'],
            'json' => ['bar' => 'baz']
        ])->willReturn('response');

        $this->update(0, 'foo', ['bar' => 'baz'], 'my-version')->shouldReturn('response');
    }

    function it_puts_a_lock_on_files($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['lock' => ['type' => 'lock', 'expires_at' => 'bar', 'is_download_prevented' => 'baz']]
        ])->shouldBeCalled();

        $this->lock(0, 'foo', 'bar', 'baz');
    }

    function it_unlocks_files($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['lock' => null]
        ])->shouldBeCalled();

        $this->unlock(0, 'foo');
    }

    function it_downloads_files($http)
    {
        $http->download('https://api.box.com/2.0/files/0/content', [
            'headers' => ['Authorization' => 'Bearer foo'],
        ], 'bar')->shouldBeCalled();

        $this->download(0, 'foo', 'bar');
    }

    function it_conducts_preflight_checks($http)
    {
        $http->options('https://api.box.com/2.0/files/content', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['name' => 'bar', 'parent' => ['id' => 0], 'size' => 100]
        ])->willReturn('response');

        $this->preflightCheck('foo', 'bar', 0, 100)->shouldReturn('response');
    }

    function it_deletes_files($http)
    {
        $http->delete('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo', 'If-Match' => 'bar'],
        ])->shouldBeCalled();

        $this->delete(0, 'foo', 'bar');
    }

    function it_uploads_new_versions_of_files($http)
    {
        $http->post('https://upload.box.com/api/2.0/files/0/content', [
            'headers' => ['Authorization' => 'Bearer foo', 'If-Match' => 'baz'],
        ], 'bar')->willReturn('response');

        $this->uploadVersion(0, 'foo', 'bar', 'baz')->shouldReturn('response');
    }

    function it_views_the_existing_versions_of_a_file($http)
    {
        $http->get('https://api.box.com/2.0/files/0/versions', [
            'headers' => ['Authorization' => 'Bearer foo'],
        ])->willReturn('response');

        $this->getVersions(0, 'foo')->shouldReturn('response');
    }

    function it_promotes_old_versions_of_files($http)
    {
        $http->post('https://api.box.com/2.0/files/0/versions/current', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['type' => 'file_version', 'id' => 100]
        ], null)->willReturn('response');

        $this->promoteVersion(0, 'foo', 100)->shouldReturn('response');
    }

    function it_deletes_specific_versions_of_files($http)
    {
        $http->delete('https://api.box.com/2.0/files/0/versions/100', [
            'headers' => ['Authorization' => 'Bearer foo'],
        ])->shouldBeCalled();

        $this->deleteVersion(0, 'foo', 100);
    }

    function it_copies_files($http)
    {
        $http->post('https://api.box.com/2.0/files/0/copy', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['parent' => ['id' => 'bar'], 'name' => 'baz']
        ], null)->willReturn('response');

        $this->copy(0, 'foo', 'bar', 'baz')->shouldReturn('response');
    }

    function it_creates_shared_links_for_files($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => [
                'shared_link' => [
                    'access' => 'bar',
                    'unshared_at' => '2015-01-01',
                    'permissions' => [
                        'can_download' => true,
                        'can_preview' => true
                    ]
                ]
            ]
        ])->willReturn('response');

        $this->createSharedLink(0, 'foo', 'bar', '2015-01-01', true, true)->shouldReturn('response');
    }

    function it_deletes_shared_links($http)
    {
        $http->put('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['shared_link' => null]
        ])->willReturn('response');

        $this->deleteSharedLink(0, 'foo')->shouldReturn('response');
    }

    function it_gets_a_trashed_file($http)
    {
        $http->get('https://api.box.com/2.0/files/0/trash', [
            'headers' => ['Authorization' => 'Bearer foo']
        ])->willReturn('response');

        $this->getTrashed(0, 'foo')->shouldReturn('response');
    }

    function it_deletes_trashed_files($http)
    {
        $http->delete('https://api.box.com/2.0/files/0/trash', [
            'headers' => ['Authorization' => 'Bearer foo']
        ])->shouldBeCalled();

        $this->deleteTrashed(0, 'foo');
    }

    function it_restores_trashed_files($http)
    {
        $http->post('https://api.box.com/2.0/files/0', [
            'headers' => ['Authorization' => 'Bearer foo'],
            'json' => ['name' => 'bar', 'parent' => ['id' => 'baz']]
        ], null)->willReturn('response');

        $this->restoreTrashed(0, 'foo', 'bar', 'baz')->shouldReturn('response');
    }

}
