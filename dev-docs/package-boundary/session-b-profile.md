# セッションB: profileドメインのビュー境界適正化

このファイルは、easy-authリポジトリのルートで新しく開始するClaude Codeセッションにそのまま渡すための指示書です。実行前に`dev-docs/package-boundary/00-plan.md`(全体アーキテクチャ・命名規約)を読み、**セッション0(`session-0-prerequisites.md`)が完了済みであること**を確認してから着手してください(未完了ならこのセッションは開始しない)。

## 対象ビュー

- `resources/views/profile/create.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/delete.blade.php`

対応するコントローラ: `ProfileController`(create/passkey登録, edit/update, delete)、`RegisterController`(パスワード登録フォールバック、`/profile-password`)。

## `profile/create.blade.php`の分割方針(判断根拠つき、要確認)

現状の`profile/create.blade.php`は以下を1ファイルに含む。

1. `#already-registered-notice`(招待保留中の再登録ガード、`$hasPendingInvitation`で表示切替)
2. `#registration-forms`という**外側のラッパーdiv**の中に、`#profile-create-form`(パスキー登録、名前入力のみ)と`#password-register-form`(パスキー不可時のパスワード登録フォールバック、`#show-password-register`ボタンで表示切替)の両方

**3つの独立したコンポーネントに分割する。** パスキー登録とパスワード登録フォールバックは現状「同一画面内でJSがトグル表示する」という1つの見せ方を採用しているが、これは`profile/create.blade.php`(デフォルトページ)の構成上の選択に過ぎず、フォームの機能自体は独立している。分割しておけば、アプリ側は「同一画面でトグル」以外に「パスキー登録ページとパスワード登録ページを別ルートに分け、遷移させる」という構成も選べる。1コンポーネントに束ねると、この選択肢自体を潰してしまう。

- `components/profile/already-registered-notice.blade.php` → `<x-easy-auth::profile.already-registered-notice />`(`$hasPendingInvitation`はpropsで受け取らず、コンポーネント内部で`session()->has('pending_invitation_token')`を直接判定してよい〈`ProfileController::create`の現在の実装と同じロジック〉。`false`なら何も描画しない)
- `components/profile/passkey-registration-form.blade.php` → `<x-easy-auth::profile.passkey-registration-form />`(`#profile-create-form`, `#passkey-status`, `#show-password-register`ボタン)
- `components/profile/password-registration-form.blade.php` → `<x-easy-auth::profile.password-registration-form />`(`#password-register-form`)

デフォルトページ(`profile/create.blade.php`)は、この3つを現状と同じ構造(`#already-registered-notice`→`#registration-forms`で残り2つをラップ)で組み合わせるだけの薄いラッパーになる。JS側の配線(`initEasyAuth()`)はどのBladeファイルが要素を描画したかを区別せず、文書全体からidで探す作りなので、この分割による変更は不要(既存動作のまま)。

## その他のコンポーネント分割

- `components/profile/edit-form.blade.php` → `<x-easy-auth::profile.edit-form />`(`$user`をpropsで受け取る)
- `components/profile/delete-confirm-form.blade.php` → `<x-easy-auth::profile.delete-confirm-form />`(`$user`をpropsで受け取る)

各ページは`@extends('layouts.app') @section('content') <x-easy-auth::profile.xxx />  @endsection`だけの薄いラッパーにする(`profile/create.blade.php`のみ上記3コンポーネントを組み合わせる分、やや厚みが残る)。`profile/create.blade.php`の`@push('scripts')`によるjs-strings出力(`nameRequired`, `passkeyLabel`, `passkeyRegistrationFailed`, `profileSaveFailed`, `passwordRegistrationFailed`, `networkError`)は、対応するコンポーネント側(`passkey-registration-form`が`nameRequired`/`passkeyLabel`/`passkeyRegistrationFailed`/`profileSaveFailed`、`password-registration-form`が`passwordRegistrationFailed`、共通の`networkError`はどちらに置いても両方から使われるため重複させてよい)に分けて持たせる。

## Layer2: 差し込みスロット

- `easy-auth::components.profile.passkey-registration-form.after-fields`
- `easy-auth::components.profile.password-registration-form.after-fields`
- `easy-auth::components.profile.edit-form.after-fields`
- `easy-auth::components.profile.delete-confirm-form.after-fields`

## Layer3: イベント

- `RegisterController::store`(パスキー登録によるUser新規作成)・`RegisterController`のパスワード登録経路(`/profile-password`)双方の完了後: `DoITs\EasyAuth\Events\UserRegistered`(登録経路が2つあるが、どちらも「Userが新規作成された」という1つの意味的イベントとして統一してよい。区別が必要なら`$event`にauth methodを持たせる形でよく、イベントクラス自体を2種類に分けない)
- `ProfileController::update`(名前編集)前後: `ProfileUpdating`/`ProfileUpdated`
- `ProfileController::destroy`または対応する自己削除処理前後: `AccountDeleting`/`AccountDeleted`(セッションAの`AccountDeletionController`用と同じイベントクラスを再利用できないか先に確認すること。`profile/delete.blade.php`のセルフサービス削除と`auth/account-deletion.blade.php`のログイン失敗契機の削除は、最終的に同じ「Userが削除された」という事象であれば同一イベントクラスを使い、発火元コントローラが違うだけにするのが望ましい。セッションAの実装が先に存在する場合はそちらのイベントクラスを`use`する)

## やらないこと

- `auth`・`tenants`・`invitations`ドメインのビュー(他セッション担当)
- `js-strings`仕組み自体の変更(セッション0で完了済みの前提)

## 完了条件

- `./vendor/bin/pest`が全件パスすること(特にパスキー登録・パスワード登録・プロフィール編集・アカウント削除関連のテスト)
- 3分割後の`profile/create.blade.php`が、これまでと同じ要素id(`already-registered-notice`, `registration-forms`, `profile-create-form`, `passkey-status`, `show-password-register`, `password-register-form`等)を同じ構造で出力していることを確認するテストを追加または既存テストの通過を確認すること(JSの配線がこのidと親子構造に依存しているため、id変更や`#registration-forms`によるラップの省略は挙動を壊す)
- `UserRegistered`/`ProfileUpdating`/`ProfileUpdated`/`AccountDeleting`/`AccountDeleted`(セッションAとの重複調整結果を反映)がFeatureテストで発火を検証されていること
- このファイルと同じディレクトリに`session-b-report.md`として、実施内容・判断に迷った点(特にAccountDeletedイベントをセッションAと共有できたか)・残課題を記録すること
