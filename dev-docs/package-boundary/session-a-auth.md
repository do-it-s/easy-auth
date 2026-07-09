# セッションA: authドメインのビュー境界適正化

このファイルは、easy-authリポジトリのルートで新しく開始するClaude Codeセッションにそのまま渡すための指示書です。実行前に`dev-docs/package-boundary/00-plan.md`(全体アーキテクチャ・命名規約)を読み、**セッション0(`session-0-prerequisites.md`)が完了済みであること**を確認してから着手してください(未完了ならこのセッションは開始しない)。

## 対象ビュー

- `resources/views/auth/sign-in.blade.php`
- `resources/views/auth/password-request.blade.php`
- `resources/views/auth/password-reset.blade.php`
- `resources/views/auth/account-deletion.blade.php`
- `resources/views/auth/account-deleted.blade.php`
- `resources/views/device/reset.blade.php`

対応するコントローラ: `SignInController`, `PasswordResetController`, `AccountDeletionController`。`device/reset.blade.php`は専用コントローラを持たない静的ページ(ルート確認要)。

## 現状の重要な違い(実装前に把握しておくこと)

このドメイン内のビューは2種類に分かれる。

1. **JS配線あり(fetch/JSON、`initEasyAuth()`が要素IDで配線)**: `sign-in.blade.php`(`#sign-in-form`, `#sign-in-status`)、`device/reset.blade.php`(`#device-uuid`, `#auth-method`, `#clear`, `#status`)
2. **通常のLaravelフォーム送信(`@csrf`+POST、リダイレクト+`old()`/`@error`で完結、JS配線なし)**: `password-request.blade.php`, `password-reset.blade.php`, `account-deletion.blade.php`

2種類目のビューにJS配線を新たに追加する必要は無い。コンポーネント化は「1つのフォーム+専用のエラー表示」という単位を保てばよく、`initEasyAuth()`側の変更は不要(このドメインでは`sign-in-form`/`device`関連の配線のみ既存のまま動かす)。

## コンポーネント分割(Layer4)

`resources/views/components/auth/`と`resources/views/components/device/`を新設し、以下の単位で切り出す。

- `components/auth/sign-in-form.blade.php` → `<x-easy-auth::auth.sign-in-form />`(現状の`#sign-in-form`+`#sign-in-status`+`session('status')`表示+パスワード再設定リンクをまとめて1コンポーネントにする。propsは不要、`session()`を内部で直接参照してよい)
- `components/auth/password-request-form.blade.php` → `<x-easy-auth::auth.password-request-form />`
- `components/auth/password-reset-form.blade.php` → `<x-easy-auth::auth.password-reset-form />`(`$token`, `$email`をpropsで受け取る)
- `components/auth/account-deletion-confirm-form.blade.php` → `<x-easy-auth::auth.account-deletion-confirm-form />`(`$user`, `$signature`, `$expires`をpropsで受け取る)
- `components/auth/account-deleted-notice.blade.php` → `<x-easy-auth::auth.account-deleted-notice />`(`id="account-deleted-page"`は`initEasyAuth()`の`clearDeviceCredentials()`トリガーに使われているため、コンポーネント側でこのidを維持すること)
- `components/device/reset-panel.blade.php` → `<x-easy-auth::device.reset-panel />`

各ページ(`auth/sign-in.blade.php`等)は`@extends('layouts.app') @section('content') <x-easy-auth::auth.xxx />  @endsection`だけを残す薄いラッパーにする。`@push('scripts')`によるjs-strings出力(`sign-in.blade.php`の`signInFailed`/`networkError`、`device/reset.blade.php`の`deviceNone`等)は、ページ側ではなく**コンポーネント側**に持たせる(コンポーネント単体でも動作が完結するように)。

## Layer2: 差し込みスロット

ループを含まないビューのみなので、`@stack`/`@push`でよい(`00-plan.md`の`@includeIf`方式はtenants/invitationsドメイン専用、このセッションでは使わない)。各フォームのsubmitボタン直前に1箇所ずつ設置する。

- `easy-auth::components.auth.sign-in-form.after-fields`
- `easy-auth::components.auth.password-request-form.after-fields`
- `easy-auth::components.auth.password-reset-form.after-fields`
- `easy-auth::components.auth.account-deletion-confirm-form.after-fields`

`account-deleted-notice`・`device/reset-panel`はフォームを持たない(前者は静的通知、後者はボタン1つ)ため、スロットは不要と判断してよい(必要性を感じたら追加して構わない)。

## Layer3: イベント

**このドメインは対象を絞る。** `SignInController::store`の`Auth::attempt()`は、Laravel標準の`Illuminate\Auth\Events\Login`/`Failed`/`Logout`を自動発火済みであり、これと重複するイベントを追加しない。デバイスUUID不一致検知(`$user->device?->uuid !== $request->header('X-Device-Uuid')`の分岐、パスワード不一致時と同一の汎用エラーメッセージを返すオラクル対策込み)は easy-auth 固有の挙動なので、任意で`DoITs\EasyAuth\Events\DeviceMismatchDetected`を追加してよいが必須ではない(監査ログ用途がなければ後回しでよい)。

以下は追加する。

- `AccountDeletionController`の削除処理前後: `AccountDeleting`/`AccountDeleted`(Laravelにはユーザー削除の汎用イベントが無いため)

`PasswordResetController`はLaravel標準の`Password`ファサード経由であれば`PasswordReset`イベントが標準で発火されている可能性が高い。**実装前に必ず確認し、既存で足りていれば追加イベントは作らないこと。**

## やらないこと

- `tenants`・`profile`・`invitations`ドメインのビュー(他セッション担当)
- `js-strings`仕組み自体の変更(セッション0で完了済みの前提)
- `resources/views/vendor/`配下への実際のオーバーライド作成(これはコンシューマアプリ側〈digital-corkboard等〉の作業であり、このセッションはパッケージ側の土台を作るところまで)

## 完了条件

- `./vendor/bin/pest`が全件パスすること(特に`tests/Feature/SignInTest.php`、パスワードリセット・アカウント削除関連のテストへの影響を確認)
- 新設した各コンポーネントについて、対応するページ(`auth/sign-in`等)をレンダリングするテストが引き続き期待通りのフォーム要素(id等)を出力することを確認するテストを追加または既存テストの通過を確認すること
- `AccountDeleting`/`AccountDeleted`(および実装した場合は他のイベント)がFeatureテストで`Event::fake()`により発火を検証されていること
- README「既知の制限・将来の検討事項」への追記は行わず(全ドメイン完了後にコントローラセッションがまとめて更新する)、代わりにこのファイルと同じディレクトリに`session-a-report.md`として、実施内容・判断に迷った点・残課題を簡潔に記録すること
