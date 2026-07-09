# セッションC: tenantsドメインのビュー境界適正化

このファイルは、easy-authリポジトリのルートで新しく開始するClaude Codeセッションにそのまま渡すための指示書です。実行前に`dev-docs/package-boundary/00-plan.md`(全体アーキテクチャ・命名規約、特に「ループ内の要素へのLayer2適用」「コピー用ボタンの共通化」の節)を読み、**セッション0が完了済みであること**を確認してから着手してください。

**このセッションは`components/shared/copy-to-clipboard-button.blade.php`を新設する担当です(`00-plan.md`参照)。** セッションDがこれに依存するため、命名・props仕様を変更する際は`00-plan.md`にも反映してください。

## 対象ビュー

- `resources/views/tenants/create.blade.php`
- `resources/views/tenants/edit.blade.php`(digital-corkboardのテナント作成許可トグルUIが将来ここに差し込まれる想定の、当初の動機となったビュー)
- `resources/views/tenants/delete.blade.php`
- `resources/views/tenants/leave.blade.php`
- `resources/views/tenants/members/index.blade.php`
- `resources/views/tenants/backup-code/show.blade.php`

対応するコントローラ: `TenantController`(create/store/edit/update/show/destroy), `TenantMemberController`(update/destroy), `TenantLeaveController`, `BackupCodeController`。

## コンポーネント分割(Layer4)

- `components/tenants/create-form.blade.php` → `<x-easy-auth::tenants.create-form />`
- `components/tenants/edit-form.blade.php` → `<x-easy-auth::tenants.edit-form />`(`$tenant`をpropsで受け取る)
- `components/tenants/delete-confirm-form.blade.php` → `<x-easy-auth::tenants.delete-confirm-form />`(`$tenant`)
- `components/tenants/leave-confirm-form.blade.php` → `<x-easy-auth::tenants.leave-confirm-form />`(`$tenant`)
- `components/tenants/member-row.blade.php` → `<x-easy-auth::tenants.member-row />`(`$tenant`, `$member`, `$isAdminSection`〈bool、admin一覧かmember一覧かでdemote/promoteのラベル・送信するroleが変わるため〉を受け取る。現状の`@forelse`内の1メンバー分のカードをそのまま切り出す)
- `components/tenants/member-list.blade.php` → `<x-easy-auth::tenants.member-list />`(`$tenant`, `$admins`, `$others`, `$adminCount`を受け取り、`member-row`を2ブロックぶんループ呼び出しする外殻)
- `components/tenants/backup-code-panel.blade.php` → `<x-easy-auth::tenants.backup-code-panel />`(`$tenant`, `$invitationUrl`, `$invitationQrCode`, `$hasUsableBackupCode`を受け取る)
- `components/shared/copy-to-clipboard-button.blade.php` → `<x-easy-auth::shared.copy-to-clipboard-button :url="..." :label="..." :label-copied="..." />`(`backup-code-panel`内のコピー機能をここに切り出す。現状ページ内にベタ書きされている`<script>`〈`document.querySelectorAll('.js-copy-invitation-url')...`〉は、コンポーネント側で完結させ、複数個所に配置されても正しく動くこと〈`querySelectorAll`で全ボタンを拾う現状のロジックのままで問題ない〉)

各ページは薄いラッパーにする。`tenants/edit.blade.php`は`@extends('layouts.app') @section('content') <x-easy-auth::tenants.edit-form /> @endsection`のみになる。

## Layer2: 差し込みスロット

- `easy-auth::components.tenants.create-form.after-fields`
- `easy-auth::components.tenants.edit-form.after-fields`(**digital-corkboardのテナント作成許可トグル2項目が最終的にここへ差し込まれる想定のスロット。命名・位置を特に慎重に決めること**〈submitボタン直前、既存の`member_invites_enabled`チェックボックスの後〉)
- `easy-auth::components.tenants.delete-confirm-form.after-fields`
- `easy-auth::components.tenants.leave-confirm-form.after-fields`

## Layer2(ループ用): `@includeIf`

`member-row.blade.php`の「demote/promote」「remove」ボタンが並ぶ`<div class="mt-2 flex gap-2">`の中に、行の末尾として以下を追加する。

```blade
@includeIf('vendor.easy-auth.tenants.member-row-actions', ['tenant' => $tenant, 'member' => $member, 'isAdminSection' => $isAdminSection])
```

デフォルトでは対象ビューが存在しないため何も描画されない。アプリ側が`resources/views/vendor/easy-auth/tenants/member-row-actions.blade.php`を作成すれば、行ごとに独自ボタン等を追加できる。

## Layer3: イベント

- `TenantController::store`前後: `TenantCreating`/`TenantCreated`
- `TenantController::update`前後: `TenantUpdating`/`TenantUpdated`(**digital-corkboardの2カラムトグルが最終的にこのイベントのリスナーで保存される想定。イベントには`$tenant`と`$request`の両方を持たせ、リスナー側で`$request->boolean('...')`等が呼べるようにすること**)
- `TenantController::destroy`前後: `TenantDeleting`/`TenantDeleted`
- `TenantMemberController::update`(役割変更)前後: `TenantMemberRoleUpdating`/`TenantMemberRoleUpdated`
- `TenantMemberController::destroy`前後: `TenantMemberRemoving`/`TenantMemberRemoved`
- `TenantLeaveController::destroy`前後: `TenantMemberRemoving`/`TenantMemberRemoved`を再利用してよい(退会も「メンバーが除かれる」という点で意味的に同じ)
- `BackupCodeController::store`(発行/再発行)前後: `BackupCodeIssuing`/`BackupCodeIssued`

## やらないこと

- `auth`・`profile`・`invitations`ドメインのビュー(他セッション担当)
- `TenantController::update`の`validate()`/`Tenant::$fillable`自体の拡張(既存方針通り、host app固有カラムはhost app側の直接代入で扱う。Eventの追加のみがこのセッションの役割)
- digital-corkboard側の実装(別リポジトリ、別セッションの作業)

## 完了条件

- `./vendor/bin/pest`が全件パスすること
- `member-row`/`member-list`分割後も、既存の`@can`による権限チェック(`updateMember`/`removeMember`)がそれぞれの行で正しく機能することを確認するテストを追加または既存テストの通過を確認すること
- `copy-to-clipboard-button`コンポーネントが2箇所(`backup-code-panel`、将来セッションDの`invitations/create`)から使われても、idやクラス名の衝突が起きない設計になっていること(`querySelectorAll`ベースなので複数個所での使用を想定した設計にする)
- 上記全イベントがFeatureテストで発火を検証されていること
- このファイルと同じディレクトリに`session-c-report.md`として、実施内容・`copy-to-clipboard-button`の最終的なprops仕様(セッションDが参照する)・残課題を記録すること
