# easy-auth

パスキー(WebAuthn) + デバイスUUID束縛によるパスワードレス認証と、招待チェーン式のテナント/ロールモデルを提供するLaravelパッケージ。`laravel/passkeys`をラップし、その上に「パスキー非対応時のメール+パスワードフォールバック」「テナント単位の招待/バックアップコード」を実装している。

## ⚠️ ベータ公開 / Beta Notice

このパッケージはまだPackagistに登録していないベータ段階です。作者はWebアプリケーション開発者であり、認証・セキュリティの専門家ではありません。設計判断には現時点で未解決の課題も残っています(下記「既知の制限・将来の検討事項」を参照)。これらを含む既知の課題を解消しながら、将来的にPackagistへの正式登録を目指しています。認証・セキュリティの知見をお持ちの方からのご指摘・フィードバックを歓迎します。

This package is in beta and not yet registered on Packagist. The author is a web application developer, not an authentication or security specialist, and some design decisions still have open questions (see "Known Limitations and Future Considerations" below). The goal is to register on Packagist once these and other known issues are addressed. Feedback from anyone with authentication or security expertise is very welcome.

## 前提

- PHP ^8.3, Laravel ^13.0
- `laravel/passkeys` ^0.2.1, `endroid/qr-code` 6.0.* (composerで自動的に入る)
- ホストアプリの標準Laravelマイグレーション(`users`, `password_reset_tokens`等)が削除されていないこと。このパッケージは`users`テーブル自体を所有せず、`password_reset_tokens`を使うパスワードリセット機能もホストアプリ側の標準テーブルにそのまま依存する

## インストール

### 1. Composerで依存追加

Packagistには未公開のため、GitHubリポジトリをVCSリポジトリとして参照する。

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/do-it-s/easy-auth.git" }
    ],
    "require": {
        "do-it-s/easy-auth": "dev-main"
    }
}
```

公開リポジトリなので認証情報の設定は不要。`dev-main`は`main`ブランチの最新コミットを指す。easy-auth側が更新された後にその変更を取り込みたい場合は、次を実行する:

```
composer update do-it-s/easy-auth
```

### 2. `laravel/passkeys`のmigrationをpublish

`laravel/passkeys`は`passkeys`テーブルのmigrationを自動ロードしない(`users.id`の型がホスト依存のため、アプリ側で編集できるようpublish方式を取っている)。

```
php artisan vendor:publish --tag=passkeys-migrations --no-interaction
```

### 3. Userモデルを結線

```php
use DoITs\EasyAuth\Concerns\IsEasyAuthUser;
use DoITs\EasyAuth\Contracts\EasyAuthUser;

class User extends Authenticatable implements EasyAuthUser
{
    use HasFactory, IsEasyAuthUser, Notifiable;
}
```

`EasyAuthUser`は`laravel/passkeys`の`PasskeyUser`をextendし、`IsEasyAuthUser`は`PasskeyAuthenticatable`をuseしているので、アプリ側はこの1interface+1traitだけで済む(`PasskeyUser`/`PasskeyAuthenticatable`を個別に書く必要はない)。

### 4. `resources/views/layouts/app.blade.php`を用意

このパッケージの全ビューは`@extends('layouts.app')`している。アプリ側のレイアウトは最低限、次を満たす必要がある:

| 要素 | 理由 |
|---|---|
| `@yield('content')` | 各ビューの`@section('content')`の差し込み先 |
| `@stack('scripts')` | `device/reset.blade.php`等が`@push('scripts')`する |
| `<meta name="csrf-token" content="{{ csrf_token() }}">` | JS側のfetchが`X-CSRF-TOKEN`に使う |
| `<meta name="auth" content="{{ auth()->check() ? '1' : '0' }}">` | トップページでの自動ログイン試行の判定に使う |
| `<p id="passkey-status"></p>` (任意のタグでよい) | 登録/自動ログイン失敗時のエラー文言の表示先。複数ページ(トップページ含む)から共有して書き込まれる |

ヘッダーのナビゲーションやブランディング、Alpine.js等のUIフレームワーク選択は上記以外、完全にアプリの自由。`$user->currentTenant()`, `$user->tenants`, `$tenant->isAdministeredBy()`, `$tenant->hasUsableBackupCode()`等のヘルパーを使ってヘッダーにテナント切替UIを組み込む場合、`@can('create', [\DoITs\EasyAuth\Models\Invitation::class, $tenant])`のようにこのパッケージのモデル名前空間を参照すること。

### 5. `home`ルートを用意

ログイン・登録・招待受け入れ・テナント切替・パスワード変更等の完了後、このパッケージは`redirect()->route('home')`で遷移する。`home`という名前のルート自体はこのパッケージは定義しないため、アプリ側で用意する必要がある。

このホーム画面は、ログイン中のユーザーがまだどのテナントにも所属していない状態(`$user->currentTenant()`が`null`を返す)を正しく表示できる必要がある。新規登録直後で招待を受けていない場合や、所属していた唯一のテナントを脱退した直後など、テナント未所属の状態は正常系として発生し得る。このパッケージはそのようなユーザーのログイン・テナント作成・招待受け入れを問題なく許可する設計であり、そのテナント未所属ユーザーを自動的に削除・整理する仕組みは現時点では持たない(将来的な検討事項)。ホーム画面側は「テナントが無い」状態を前提に分岐を用意すること。

### 6. マイグレーション実行

```
php artisan migrate
```

### 7. フロントエンド(JS)

`resources/js/`にpasskey登録/ログイン/パスワードフォールバックのJSが`@do-it-s/easy-auth-js`としてパッケージ化されている。アプリ側の`package.json`に追加:

```json
{
    "dependencies": {
        "@do-it-s/easy-auth-js": "file:../easy-auth/resources/js"
    }
}
```

アプリの`resources/js/app.js`:

```js
import { initEasyAuth } from '@do-it-s/easy-auth-js';

initEasyAuth();
```

**注意:** `file:`参照はリポジトリ外のディレクトリへのシンボリックリンクとして`node_modules`に入るだけで、npmはリンク先(`easy-auth/resources/js`)が宣言している`@laravel/passkeys`を**アプリ側のnode_modulesに自動で解決してくれない**(npm workspacesではない単純な`file:`依存は、対象が自分のリポジトリ外にあると依存解決を再帰しない)。Node/Viteのモジュール解決はシンボリックリンクの実体パスを辿って`node_modules`を探すため、`@laravel/passkeys`は**`easy-auth/resources/js`自身の`node_modules`に存在している必要がある**。つまりeasy-auth側で一度(またはpackage.json変更ごとに):

```
cd easy-auth/resources/js
npm install
```

を実行しておくこと。これを忘れるとアプリ側の`npm run build`/`npm run dev`が`Failed to resolve import "@laravel/passkeys" from ".../easy-auth/resources/js/index.js"`で失敗する。

(Alpine.js等、アプリ自身のJS初期化はこの前後で好きに行ってよい。easy-auth-jsはAlpineに依存していない。)

Windowsで`file:`依存がsymlink権限エラーになる場合は`.npmrc`に`install-links=true`を追加してコピーインストールに切り替えること。

### 8. 例外ハンドラがJSONを返せることを確認

このパッケージの`/login`・`/profile-password`等は`api/*`配下ではなく、`Accept: application/json`ヘッダーによる通常のコンテンツネゴシエーション(`Request::expectsJson()`のデフォルト挙動)でJSONを返す設計になっている。

`bootstrap/app.php`に次のような記述があると、`api/*`以外のルートでは常にHTMLレスポンスが強制され、`ValidationException`等が302リダイレクト(HTML)で返ってしまう。JS側は`response.json()`でその`<!DOCTYPE ...`をパースしようとして`Unexpected token '<' ... is not valid JSON`で失敗する:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*'),
    );
})
```

これはLaravelの新規プロジェクト雛形に含まれることがあるが、`routes/api.php`を持たないアプリではAPIルートを一切JSON化できないだけの制約になっている。easy-auth導入時はこのコールバックを削除する(空の`withExceptions`に戻す)か、`$request->is('api/*') || $request->expectsJson()`に書き換えること。

## 既知の制限・将来の検討事項

- エラー表示(`#passkey-status`, `#login-status`への直接`textContent`書き込み)は現状ハードコードされており、アプリ側でトースト通知等の独自UIに差し替えることはできない。将来`initEasyAuth({ onMessage })`のようなコールバックオプションを追加して分離する余地がある(後方互換を崩さずに追加可能)。
- パッケージ提供のビュー(`tenants/create.blade.php`等)に`@stack`/`@yield`等の差し込みポイントが無く、`vendor:publish`の対象にもなっていない。`TenantController::store`/`update`等の`validate()`が受け付けるフィールドや`Tenant`モデルの`fillable`もハードコードされており、アプリ側がテナント作成フォーム等に独自項目を追加する手段が無い。
- テナント作成・ユーザー登録・招待redeem・脱退・アカウント削除など、主要な操作の前後にLaravel Eventが一つも発火しない。アプリ側で監査ログや外部サービス連携等のフックを挿入する手段が無い。
- `EnsureProfileIsComplete`ミドルウェアは`$user->name === ''`のみでプロフィール完了を判定する。アプリが独自の必須プロフィール項目(電話番号等)を追加しても、このミドルウェアは関知せず素通りしてしまう。
- パスキー登録時のWebAuthnオプション(`userVerification`・`residentKey`)は`laravel/passkeys`のデフォルト値(いずれも`required`)に委ねており、easy-auth側で上書きする設定項目は無い。
- 登録時にAuthenticatorのBE(Backup Eligible)フラグを確認し、クラウド同期可能なパスキー(iCloudキーチェーン等で複数デバイスに同期されるもの)の登録を拒否する機能を`DoITs\EasyAuth\Actions\RejectSyncedPasskey`として用意している。デバイスUUID束縛は「招待されたデバイスである」ことの証明であり、同期パスキーはこの保証を(招待redeem時に組織外のデバイスでも人の正当性チェックを通過できてしまう形で)弱め得る。しかしこの拒否を有効にすると、ブラウザのパスワードマネージャー拡張機能(1Password等)がOS側のパスキー選択ダイアログからプラットフォーム認証器(Windows Hello等)を実質排除してしまう環境で、デバイス専用パスキーを新規作成する手段が無くなり、登録自体が不可能になるケースが実機で確認された。かんたん認証はメール/パスワードへのフォールバックを意図的に持たない設計のため、この詰みは「組織境界が同期パスキー分だけ弱まる」リスクより重いと判断し、`config('easy-auth.reject_backup_eligible_passkeys')`(`.env`の`EASY_AUTH_REJECT_BACKUP_ELIGIBLE_PASSKEYS`)でデフォルトoffにしている。サービス単位でoffからonに切り替える設定は用意した(`php artisan vendor:publish --tag=easy-auth-config`または`.env`で上書き)。`authenticatorAttachment`をplatformに強制するなどして選択ダイアログ自体から同期可能な選択肢を除外できないかは今後の検討事項。
