# easy-auth

[English](README.md) | 日本語

パスキー(WebAuthn) + デバイスUUID束縛によるパスワードレス認証と、招待チェーン式のテナント/ロールモデルを提供するLaravelパッケージ。`laravel/passkeys`をラップし、その上に「パスキー非対応時のメール+パスワードフォールバック」「テナント単位の招待/バックアップコード」を実装している。

## ⚠️ ベータ公開

このパッケージはPackagistに登録済みですが、まだ0.x系のベータ段階です。作者はWebアプリケーション開発者であり、認証・セキュリティの専門家ではありません。設計判断には現時点で未解決の課題も残っています(下記「既知の制限・将来の検討事項」を参照)。これらを含む既知の課題を解消しながら、将来的に1.0への到達を目指しています。認証・セキュリティの知見をお持ちの方からのご指摘・フィードバックを歓迎します。

## 前提

- PHP ^8.3, Laravel ^13.0
- `laravel/passkeys` ^0.2.1, `endroid/qr-code` 6.0.* (composerで自動的に入る)
- ホストアプリの標準Laravelマイグレーション(`users`, `password_reset_tokens`等)が削除されていないこと。このパッケージは`users`テーブル自体を所有せず、`password_reset_tokens`を使うパスワードリセット機能もホストアプリ側の標準テーブルにそのまま依存する

## インストール

### 1. Composerで依存追加

```
composer require do-it-s/easy-auth
```

easy-auth側が更新された後にその変更を取り込みたい場合は、次を実行する:

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

ヘッダーのナビゲーションやブランディング、Alpine.js等のUIフレームワーク選択は上記以外、完全にアプリの自由。`$user->currentTenant()`, `$user->tenants`, `$tenant->isAdministeredBy()`, `$tenant->hasUsableBackupCode()`等のヘルパーを使ってヘッダーにテナント切替UIを組み込む場合、`@can('create', [\DoITs\EasyAuth\Models\Invitation::class, $tenant])`のようにこのパッケージのモデル名前空間を参照すること。

### 5. `home`ルートを用意

サインイン・登録・招待受け入れ・テナント切替・パスワード変更等の完了後、このパッケージは`redirect()->route('home')`で遷移する。`home`という名前のルート自体はこのパッケージは定義しないため、アプリ側で用意する必要がある。

このホーム画面は、サインイン中のユーザーがまだどのテナントにも所属していない状態(`$user->currentTenant()`が`null`を返す)を正しく表示できる必要がある。新規登録直後で招待を受けていない場合や、所属していた唯一のテナントを脱退した直後など、テナント未所属の状態は正常系として発生し得る。このパッケージはそのようなユーザーのサインイン・テナント作成・招待受け入れを問題なく許可する設計であり、そのテナント未所属ユーザーを自動的に削除・整理する仕組みは現時点では持たない(将来的な検討事項)。ホーム画面側は「テナントが無い」状態を前提に分岐を用意すること。

### 6. マイグレーション実行

```
php artisan migrate
```

### 7. フロントエンド(JS)

`resources/js/`にpasskey登録/サインイン/パスワードフォールバックのJSが`@do-it-s/easy-auth-js`としてパッケージ化されている。アプリ側の`package.json`に追加:

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

#### サインイン機能(`canAttemptSignIn` / `attemptSignIn`)

WebAuthnの儀式はユーザの明確な操作を起点に試行すべきという原則から、このパッケージはページ到達時の自動サインイン試行を行わない。代わりに、ホストアプリ自身のguest home(トップページ等)に「サインイン」UIを置けるよう、2つの関数を提供する。UIの表示/非表示の決定・試行結果を受けた後の画面遷移は、すべてアプリ側の責務になる。

```js
import { canAttemptSignIn, attemptSignIn } from '@do-it-s/easy-auth-js';

if (canAttemptSignIn()) {
    // 「サインイン」ボタンなど、明示的な操作を要するUIを表示する。
}

signInButton.addEventListener('click', async () => {
    const { outcome, redirect } = await attemptSignIn();

    if (outcome === 'success') {
        window.location.href = redirect;
    } else if (outcome === 'fallback') {
        // このデバイスはパスワード認証ユーザー。パッケージの/loginへ案内する。
        window.location.href = '/login';
    } else {
        // outcome === 'failure'。キャンセル・該当パスキー無し・サーバ拒否のいずれかだが、
        // アプリ側でこれ以上の種類分けはできない/する必要がない。
    }
});
```

- `canAttemptSignIn()`は`device_uuid`の有無だけを見た**目安**であり、サインインが必ず成功する保証ではない(WebAuthnの仕様上、儀式を行わずに「該当する認証情報があるか」を確実に知る方法は無い)。
- `attemptSignIn()`はクリックなど明確な操作からのみ呼ぶこと。DOM書き込み・画面遷移は一切行わないため、`outcome`に応じた表示・遷移は呼び出し側で用意する。
- 旧バージョンはトップページ到達時に自動でサインインを試行していたが、本バージョンではこの自動試行を廃止した。アプリ側で上記のような明示的なUIを用意しないと、登録済みデバイスでもサインインできなくなる(後方互換を破る変更)。

#### 登録・パスワードサインイン機能(プリミティブ / `initEasyAuth`)

`registerPasskey` / `registerWithPassword` / `signInWithPassword`はDOM読み書きを一切行わないプリミティブで、`{ outcome: 'success', redirect }`または`{ outcome: 'failure', code, errors? }`を返す。`code`は`name_required` / `ceremony_failed` / `validation` / `server_error` / `network_error`のいずれか(`validation`の時だけ`errors`にLaravel側で翻訳済みのフィールド別メッセージが入る)。独自フォーム・独自UIをアプリ側で完全に組みたい場合はこれらを直接呼ぶ。

```js
import { registerPasskey, registerWithPassword, signInWithPassword } from '@do-it-s/easy-auth-js';

const result = await registerPasskey({ name, passkeyLabel: 'My device' });

if (result.outcome === 'success') {
    window.location.href = result.redirect;
} else {
    // result.code に応じて自前のUIで表示する
}
```

このパッケージ自身が同梱するデフォルトビュー(`profile/create.blade.php`, `auth/sign-in.blade.php`等)の既知のフォームIDにだけ配線する便利関数が`initEasyAuth()`で、これが上記プリミティブ+パスキー非対応時のパスワードフォーム自動切替のようなデフォルトビュー固有のUI気配りをまとめて行う。独自ビューを使うアプリはこの関数を呼ばなければ、この配線・UI気配りは自然に無効化される。

```js
import { initEasyAuth } from '@do-it-s/easy-auth-js';

initEasyAuth();
```

デフォルトでは失敗時にこのパッケージ自身のビューが持つ状態表示要素(`#passkey-status`, `#sign-in-status`)に`textContent`で書き込む。トースト通知等、アプリ独自のUIに差し替えたい場合は`onStatus`を渡す(フォーム配線・WebAuthn儀式の呼び出し自体は`initEasyAuth()`に任せたまま、表示だけ差し替えられる):

```js
initEasyAuth({
    onStatus: ({ outcome, code, message }) => {
        showToast(message, outcome === 'success' ? 'alert-success' : 'alert-error');
    },
});
```

`message`はこのパッケージが用意した翻訳済み文言(サーバーバリデーションの場合は`errors`を結合したもの)。`code`は`outcome: 'failure'`の内訳で、必要ならさらに細かい分岐に使える。

#### デバイスの認証情報(`getDeviceCredentials` / `clearDeviceCredentials`)

`device_uuid`/`auth_method`という2つのlocalStorageキーを直接読み書きする代わりに使う。

```js
import { getDeviceCredentials, clearDeviceCredentials } from '@do-it-s/easy-auth-js';

const { device_uuid, auth_method } = getDeviceCredentials();
```

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

## ビューのカスタマイズ

easy-authが提供するビューは、アプリが何も用意しなくても認証フロー一式が動く(デフォルト完結)ことを保証しつつ、以下の4つの手段でアプリ側からカスタマイズできる。

### 1. ページ全体・コンポーネント単位の差し替え(`vendor:publish`)

```
php artisan vendor:publish --tag=easy-auth-views
```

`resources/views/vendor/easy-auth/`にビュー一式がコピーされ、以後はコピーされたファイルが優先して使われる(Laravel標準のビュー名前空間解決)。`resources/views/`配下は「1画面=1ページ」(例: `auth/sign-in.blade.php`)と、実際のフォーム等を持つ「機能コンポーネント」(`resources/views/components/`配下、例: `components/auth/sign-in-form.blade.php`)の2層構造になっている。ページの見た目だけを変えたいならページ側だけをコピーして`<x-easy-auth::auth.sign-in-form />`は元のコンポーネントのまま使い続けることもできるし、コンポーネント自体を丸ごと差し替えることもできる。

サインイン・パスワード再設定リンク送信・プロフィール編集・組織編集の各フォームが表示する成功時ステータス(`session('status')`の緑字メッセージ)は、フォーム全体とは別に`components/shared/status-message.blade.php`という小さな共有コンポーネントに切り出されている。アプリ側で(例えばトースト通知に一本化するなどの理由で)このメッセージだけを消したい場合、フォーム全体をコピーして編集する必要はなく、`resources/views/vendor/easy-auth/components/shared/status-message.blade.php`を空ファイルとして作成すれば済む。

### 2. 既存フォームへの項目追加(`@stack`)

主要なフォームコンポーネントには、submitボタン直前に`@stack('easy-auth::components.{domain}.{name}.after-fields')`という差し込みポイントがある(例: `tenants/edit`フォームなら`easy-auth::components.tenants.edit-form.after-fields`)。アプリ側は任意のビューから`@push`するだけで、ページをコピーせずに項目を追加できる。

```blade
@push('easy-auth::components.tenants.edit-form.after-fields')
    <label class="flex items-center gap-2 mb-4 text-sm">
        <input type="checkbox" name="my_app_flag" value="1">
        独自の設定項目
    </label>
@endpush
```

一覧系ビュー(`tenants/members/index`, `tenants/invitations/index`)の各行には、`@stack`ではなく`@includeIf('vendor.easy-auth.tenants.member-row-actions', [...])`のようなループ対応の差し込みポイントがある。デフォルトでは何も描画されず、アプリ側が`resources/views/vendor/easy-auth/tenants/member-row-actions.blade.php`(渡される変数はコンポーネントごとに異なる。各コンポーネントのソースを参照)を作成すると、行ごとに独自のボタン等を追加できる。

### 3. 変更操作前後のフック(Laravel Event)

テナント更新・招待作成・メンバー削除等の主要な変更操作は、前後に`DoITs\EasyAuth\Events\`配下のイベントを発火する(例: `TenantUpdating`/`TenantUpdated`, `InvitationCreating`/`InvitationCreated`)。`*ing`系イベントは検証済み配列ではなく生の`Request`(または元データ)を保持しているため、リスナー側で自分のフィールドを読み取り、`fillable`に含まれないカラムでも直接代入で保存できる。

```php
Event::listen(TenantUpdating::class, function (TenantUpdating $event): void {
    $event->tenant->my_app_column = $event->request->boolean('my_app_column');
    $event->tenant->save();
});
```

このパッケージが発火するイベントは以下の25個。`{Model}{Verb}ing`/`{Model}{Verb}ed`という命名はEloquentの`Creating`/`Created`系と同型で、全ての変更操作を一括で網羅している(実際にリスナーで使われている例は現状少ないが、将来アプリ側が任意の操作にフックできるよう先回りして揃えたもの)。

| 契機 | 発火元 | `*ing`(検証前) | `*ed`(保存後) |
| --- | --- | --- | --- |
| 新規登録(パスワード) | `Auth\RegisterController` | - | `UserRegistered`(`context: 'password'`) |
| 新規登録(パスキー) | `ProfileController` | - | `UserRegistered`(`context: 'passkey'`) |
| プロフィール編集 | `ProfileController` | `ProfileUpdating` | `ProfileUpdated` |
| アカウント削除(ログイン中の本人操作) | `ProfileController` | `AccountDeleting` | `AccountDeleted` |
| アカウント削除(デバイス不一致からのセルフサービス退会) | `Auth\AccountDeletionController` | `AccountDeleting` | `AccountDeleted` |
| パスワード再設定 | `Auth\PasswordResetController` | `PasswordResetting` | `PasswordResetCompleted` |
| バックアップコード発行 | `BackupCodeController` | `BackupCodeIssuing` | `BackupCodeIssued` |
| 招待作成 | `InvitationController` | `InvitationCreating` | `InvitationCreated` |
| 招待失効 | `InvitationController` | `InvitationRevoking` | `InvitationRevoked` |
| 招待redeem(参加) | `InvitationRedemptionController` | `InvitationRedeeming` | `InvitationRedeemed` |
| 組織作成 | `TenantController` | `TenantCreating` | `TenantCreated` |
| 組織編集 | `TenantController` | `TenantUpdating` | `TenantUpdated` |
| 組織削除 | `TenantController` | `TenantDeleting` | `TenantDeleted` |
| メンバー自己脱退 | `TenantLeaveController` | `TenantMemberRemoving` | `TenantMemberRemoved` |
| メンバー削除(管理者操作) | `TenantMemberController` | `TenantMemberRemoving` | `TenantMemberRemoved` |
| メンバーのロール変更 | `TenantMemberController` | `TenantMemberRoleUpdating` | `TenantMemberRoleUpdated` |

### 4. 機能コンポーネントの組み替え

`resources/views/components/`配下のコンポーネントは、パッケージ側のデフォルトページでの組み合わせ方に縛られない自己完結設計になっている。例えば`profile/create`はパスキー登録(`passkey-registration-form`)とパスワード登録フォールバック(`password-registration-form`)を独立したコンポーネントとして提供しているため、アプリ側は両者を同一画面に並べる・別ルートに分ける・片方だけ使う、といった構成を自由に選べる。

## ルートのカスタマイズ

上記4つの手段は「既存のルートの中身」を差し替えるものであり、ルートそのもの(URL・HTTPメソッド・使うかどうか)は対象外。アプリがURLを変えたい、特定の機能(パスワード登録フォールバック等)のルート自体を無くしたい、といった場合は、`AppServiceProvider::register()`で`EasyAuth::ignoreRoutes()`を呼ぶ。

```php
use DoITs\EasyAuth\EasyAuth;

public function register(): void
{
    EasyAuth::ignoreRoutes();
}
```

これを呼ぶと、このパッケージは`routes/web.php`を一切登録しなくなる。以後はアプリ自身の`routes/web.php`に、このパッケージのコントローラ(`DoITs\EasyAuth\Http\Controllers\...`)を指定して好きなURL・ミドルウェアでルートを書く(このパッケージ自身の`routes/web.php`をコピー元にすると早い)。使わない機能のルートは単に書かなければよい。

## 翻訳文言のカスタマイズ

このパッケージのビュー・メール文言はすべて`easy-auth::`名前空間の翻訳キー経由(`lang/en`, `lang/ja`)。Laravel標準の仕組みにより、`lang/vendor/easy-auth/{locale}/`配下に同名ファイルを置けば自動的に上書きされる(コード変更・追加設定は不要)。ひな形が欲しい場合は以下でコピーできる。

```
php artisan vendor:publish --tag=easy-auth-lang
```

## 既知の制限・将来の検討事項

- `EnsureProfileIsComplete`ミドルウェアは`$user->name === ''`のみでプロフィール完了を判定する。アプリが独自の必須プロフィール項目(電話番号等)を追加しても、このミドルウェアは関知せず素通りしてしまう。
- パスキー登録時のWebAuthnオプション(`userVerification`・`residentKey`)は`laravel/passkeys`のデフォルト値(いずれも`required`)に委ねており、easy-auth側で上書きする設定項目は無い。
- 登録時にAuthenticatorのBE(Backup Eligible)フラグを確認し、クラウド同期可能なパスキー(iCloudキーチェーン等で複数デバイスに同期されるもの)の登録を拒否する機能を`DoITs\EasyAuth\Actions\RejectSyncedPasskey`として用意している。デバイスUUID束縛は「招待されたデバイスである」ことの証明であり、同期パスキーはこの保証を(招待redeem時に組織外のデバイスでも人の正当性チェックを通過できてしまう形で)弱め得る。しかしこの拒否を有効にすると、ブラウザのパスワードマネージャー拡張機能(1Password等)がOS側のパスキー選択ダイアログからプラットフォーム認証器(Windows Hello等)を実質排除してしまう環境で、デバイス専用パスキーを新規作成する手段が無くなり、登録自体が不可能になるケースが実機で確認された。かんたん認証はメール/パスワードへのフォールバックを意図的に持たない設計のため、この詰みは「組織境界が同期パスキー分だけ弱まる」リスクより重いと判断し、`config('easy-auth.reject_backup_eligible_passkeys')`(`.env`の`EASY_AUTH_REJECT_BACKUP_ELIGIBLE_PASSKEYS`)でデフォルトoffにしている。サービス単位でoffからonに切り替える設定は用意した(`php artisan vendor:publish --tag=easy-auth-config`または`.env`で上書き)。
- 登録時に`authenticatorAttachment`をplatformに強制し、選択ダイアログ自体からクロスプラットフォーム認証器・パスワードマネージャーのみの選択肢を除外する機能を`config('easy-auth.force_platform_authenticator')`(`.env`の`EASY_AUTH_FORCE_PLATFORM_AUTHENTICATOR`)で用意している。デフォルトoff、かつ今後もoffのままにする方針。Windows 11 + 1Password環境で実機検証したところ、`authenticatorAttachment: platform`を強制しても1Passwordは選択ダイアログから排除されず選択可能なまま残り、それを選んだ場合`reject_backup_eligible_passkeys`のBE拒否に引っかかって完了できない、という結果になった。この設定が意図した「Helloだけに絞り込む」効果はブラウザ・拡張機能の組み合わせによっては働かないと確認できたため、onにする積極的な理由が無いと判断し、offをデフォルトかつ最終的な結論とした。
- 招待の「最大使用回数」を1より大きく設定できる(1つの招待リンクを複数人で使い回せる)機能は`config('easy-auth.multi_use_invitations')`(`.env`の`EASY_AUTH_MULTI_USE_INVITATIONS`)でデフォルトoffにしている。デバイスUUID束縛・招待チェインという設計は「誰が誰を招待したか」の暗黙の追跡を前提としており、複数回redeem可能な招待はこれを弱める。加えてこの項目はシステム管理者的な判断を要するものであり、一般のテナント管理者・メンバーが自由に触れる設定であるべきではないと判断した。offの間は招待フォームから「最大使用回数」欄自体が消え、送信された値の有無に関わらずサーバー側で強制的に`max_uses=1`(単一使用)になる。
