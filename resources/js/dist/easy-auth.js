// node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js
function bufferToBase64URLString(buffer) {
  const bytes = new Uint8Array(buffer);
  let str = "";
  for (const charCode of bytes) {
    str += String.fromCharCode(charCode);
  }
  const base64String = btoa(str);
  return base64String.replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
}

// node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js
function base64URLStringToBuffer(base64URLString) {
  const base64 = base64URLString.replace(/-/g, "+").replace(/_/g, "/");
  const padLength = (4 - base64.length % 4) % 4;
  const padded = base64.padEnd(base64.length + padLength, "=");
  const binary = atob(padded);
  const buffer = new ArrayBuffer(binary.length);
  const bytes = new Uint8Array(buffer);
  for (let i2 = 0; i2 < binary.length; i2++) {
    bytes[i2] = binary.charCodeAt(i2);
  }
  return buffer;
}

// node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js
function browserSupportsWebAuthn() {
  return _browserSupportsWebAuthnInternals.stubThis(globalThis?.PublicKeyCredential !== void 0 && typeof globalThis.PublicKeyCredential === "function");
}
var _browserSupportsWebAuthnInternals = {
  stubThis: (value) => value
};

// node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js
function toPublicKeyCredentialDescriptor(descriptor) {
  const { id } = descriptor;
  return {
    ...descriptor,
    id: base64URLStringToBuffer(id),
    /**
     * `descriptor.transports` is an array of our `AuthenticatorTransportFuture` that includes newer
     * transports that TypeScript's DOM lib is ignorant of. Convince TS that our list of transports
     * are fine to pass to WebAuthn since browsers will recognize the new value.
     */
    transports: descriptor.transports
  };
}

// node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js
function isValidDomain(hostname) {
  return (
    // Consider localhost valid as well since it's okay wrt Secure Contexts
    hostname === "localhost" || // Support punycode (ACE) or ascii labels and domains
    /^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(hostname)
  );
}

// node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js
var WebAuthnError = class extends Error {
  constructor({ message, code, cause, name }) {
    super(message, { cause });
    Object.defineProperty(this, "code", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.name = name ?? cause.name;
    this.code = code;
  }
};

// node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js
function identifyRegistrationError({ error, options }) {
  const { publicKey } = options;
  if (!publicKey) {
    throw Error("options was missing required publicKey property");
  }
  if (error.name === "AbortError") {
    if (options.signal instanceof AbortSignal) {
      return new WebAuthnError({
        message: "Registration ceremony was sent an abort signal",
        code: "ERROR_CEREMONY_ABORTED",
        cause: error
      });
    }
  } else if (error.name === "ConstraintError") {
    if (publicKey.authenticatorSelection?.requireResidentKey === true) {
      return new WebAuthnError({
        message: "Discoverable credentials were required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT",
        cause: error
      });
    } else if (
      // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
      options.mediation === "conditional" && publicKey.authenticatorSelection?.userVerification === "required"
    ) {
      return new WebAuthnError({
        message: "User verification was required during automatic registration but it could not be performed",
        code: "ERROR_AUTO_REGISTER_USER_VERIFICATION_FAILURE",
        cause: error
      });
    } else if (publicKey.authenticatorSelection?.userVerification === "required") {
      return new WebAuthnError({
        message: "User verification was required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT",
        cause: error
      });
    }
  } else if (error.name === "InvalidStateError") {
    return new WebAuthnError({
      message: "The authenticator was previously registered",
      code: "ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED",
      cause: error
    });
  } else if (error.name === "NotAllowedError") {
    return new WebAuthnError({
      message: error.message,
      code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
      cause: error
    });
  } else if (error.name === "NotSupportedError") {
    const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === "public-key");
    if (validPubKeyCredParams.length === 0) {
      return new WebAuthnError({
        message: 'No entry in pubKeyCredParams was of type "public-key"',
        code: "ERROR_MALFORMED_PUBKEYCREDPARAMS",
        cause: error
      });
    }
    return new WebAuthnError({
      message: "No available authenticator supported any of the specified pubKeyCredParams algorithms",
      code: "ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG",
      cause: error
    });
  } else if (error.name === "SecurityError") {
    const effectiveDomain = globalThis.location.hostname;
    if (!isValidDomain(effectiveDomain)) {
      return new WebAuthnError({
        message: `${globalThis.location.hostname} is an invalid domain`,
        code: "ERROR_INVALID_DOMAIN",
        cause: error
      });
    } else if (publicKey.rp.id !== effectiveDomain) {
      return new WebAuthnError({
        message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
        code: "ERROR_INVALID_RP_ID",
        cause: error
      });
    }
  } else if (error.name === "TypeError") {
    if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
      return new WebAuthnError({
        message: "User ID was not between 1 and 64 characters",
        code: "ERROR_INVALID_USER_ID_LENGTH",
        cause: error
      });
    }
  } else if (error.name === "UnknownError") {
    return new WebAuthnError({
      message: "The authenticator was unable to process the specified options, or could not create a new credential",
      code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
      cause: error
    });
  }
  return error;
}

// node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js
var BaseWebAuthnAbortService = class {
  constructor() {
    Object.defineProperty(this, "controller", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  createNewAbortSignal() {
    if (this.controller) {
      const abortError = new Error("Cancelling existing WebAuthn API call for new one");
      abortError.name = "AbortError";
      this.controller.abort(abortError);
    }
    const newController = new AbortController();
    this.controller = newController;
    return newController.signal;
  }
  cancelCeremony() {
    if (this.controller) {
      const abortError = new Error("Manually cancelling existing WebAuthn API call");
      abortError.name = "AbortError";
      this.controller.abort(abortError);
      this.controller = void 0;
    }
  }
};
var WebAuthnAbortService = new BaseWebAuthnAbortService();

// node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js
var attachments = ["cross-platform", "platform"];
function toAuthenticatorAttachment(attachment) {
  if (!attachment) {
    return;
  }
  if (attachments.indexOf(attachment) < 0) {
    return;
  }
  return attachment;
}

// node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js
async function startRegistration(options) {
  if (!options.optionsJSON && options.challenge) {
    console.warn("startRegistration() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
    options = { optionsJSON: options };
  }
  const { optionsJSON, useAutoRegister = false } = options;
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  const publicKey = {
    ...optionsJSON,
    challenge: base64URLStringToBuffer(optionsJSON.challenge),
    user: {
      ...optionsJSON.user,
      id: base64URLStringToBuffer(optionsJSON.user.id)
    },
    excludeCredentials: optionsJSON.excludeCredentials?.map(toPublicKeyCredentialDescriptor)
  };
  const createOptions = {};
  if (useAutoRegister) {
    createOptions.mediation = "conditional";
  }
  createOptions.publicKey = publicKey;
  createOptions.signal = WebAuthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.create(createOptions);
  } catch (err) {
    throw identifyRegistrationError({ error: err, options: createOptions });
  }
  if (!credential) {
    throw new Error("Registration was not completed");
  }
  const { id, rawId, response, type } = credential;
  let transports = void 0;
  if (typeof response.getTransports === "function") {
    transports = response.getTransports();
  }
  let responsePublicKeyAlgorithm = void 0;
  if (typeof response.getPublicKeyAlgorithm === "function") {
    try {
      responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
    } catch (error) {
      warnOnBrokenImplementation("getPublicKeyAlgorithm()", error);
    }
  }
  let responsePublicKey = void 0;
  if (typeof response.getPublicKey === "function") {
    try {
      const _publicKey = response.getPublicKey();
      if (_publicKey !== null) {
        responsePublicKey = bufferToBase64URLString(_publicKey);
      }
    } catch (error) {
      warnOnBrokenImplementation("getPublicKey()", error);
    }
  }
  let responseAuthenticatorData;
  if (typeof response.getAuthenticatorData === "function") {
    try {
      responseAuthenticatorData = bufferToBase64URLString(response.getAuthenticatorData());
    } catch (error) {
      warnOnBrokenImplementation("getAuthenticatorData()", error);
    }
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      attestationObject: bufferToBase64URLString(response.attestationObject),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      transports,
      publicKeyAlgorithm: responsePublicKeyAlgorithm,
      publicKey: responsePublicKey,
      authenticatorData: responseAuthenticatorData
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}
function warnOnBrokenImplementation(methodName, cause) {
  console.warn(`The browser extension that intercepted this WebAuthn API call incorrectly implemented ${methodName}. You should report this error to them.
`, cause);
}

// node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js
function browserSupportsWebAuthnAutofill() {
  if (!browserSupportsWebAuthn()) {
    return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
  }
  const globalPublicKeyCredential = globalThis.PublicKeyCredential;
  if (globalPublicKeyCredential?.isConditionalMediationAvailable === void 0) {
    return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
  }
  return _browserSupportsWebAuthnAutofillInternals.stubThis(globalPublicKeyCredential.isConditionalMediationAvailable());
}
var _browserSupportsWebAuthnAutofillInternals = {
  stubThis: (value) => value
};

// node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js
function identifyAuthenticationError({ error, options }) {
  const { publicKey } = options;
  if (!publicKey) {
    throw Error("options was missing required publicKey property");
  }
  if (error.name === "AbortError") {
    if (options.signal instanceof AbortSignal) {
      return new WebAuthnError({
        message: "Authentication ceremony was sent an abort signal",
        code: "ERROR_CEREMONY_ABORTED",
        cause: error
      });
    }
  } else if (error.name === "NotAllowedError") {
    return new WebAuthnError({
      message: error.message,
      code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
      cause: error
    });
  } else if (error.name === "SecurityError") {
    const effectiveDomain = globalThis.location.hostname;
    if (!isValidDomain(effectiveDomain)) {
      return new WebAuthnError({
        message: `${globalThis.location.hostname} is an invalid domain`,
        code: "ERROR_INVALID_DOMAIN",
        cause: error
      });
    } else if (publicKey.rpId !== effectiveDomain) {
      return new WebAuthnError({
        message: `The RP ID "${publicKey.rpId}" is invalid for this domain`,
        code: "ERROR_INVALID_RP_ID",
        cause: error
      });
    }
  } else if (error.name === "UnknownError") {
    return new WebAuthnError({
      message: "The authenticator was unable to process the specified options, or could not create a new assertion signature",
      code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
      cause: error
    });
  }
  return error;
}

// node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js
async function startAuthentication(options) {
  if (!options.optionsJSON && options.challenge) {
    console.warn("startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
    options = { optionsJSON: options };
  }
  const { optionsJSON, useBrowserAutofill = false, verifyBrowserAutofillInput = true } = options;
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  let allowCredentials;
  if (optionsJSON.allowCredentials?.length !== 0) {
    allowCredentials = optionsJSON.allowCredentials?.map(toPublicKeyCredentialDescriptor);
  }
  const publicKey = {
    ...optionsJSON,
    challenge: base64URLStringToBuffer(optionsJSON.challenge),
    allowCredentials
  };
  const getOptions = {};
  if (useBrowserAutofill) {
    if (!await browserSupportsWebAuthnAutofill()) {
      throw Error("Browser does not support WebAuthn autofill");
    }
    const eligibleInputs = document.querySelectorAll("input[autocomplete$='webauthn']");
    if (eligibleInputs.length < 1 && verifyBrowserAutofillInput) {
      throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');
    }
    getOptions.mediation = "conditional";
    publicKey.allowCredentials = [];
  }
  getOptions.publicKey = publicKey;
  getOptions.signal = WebAuthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.get(getOptions);
  } catch (err) {
    throw identifyAuthenticationError({ error: err, options: getOptions });
  }
  if (!credential) {
    throw new Error("Authentication was not completed");
  }
  const { id, rawId, response, type } = credential;
  let userHandle = void 0;
  if (response.userHandle) {
    userHandle = bufferToBase64URLString(response.userHandle);
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      authenticatorData: bufferToBase64URLString(response.authenticatorData),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      signature: bufferToBase64URLString(response.signature),
      userHandle
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}

// node_modules/@laravel/passkeys/dist/passkeys-yRK3MD0m.js
var i = class extends Error {
  constructor(e) {
    super(e), this.name = "PasskeyError";
  }
};
var d = class extends i {
  constructor() {
    super("Passkeys are not supported in this browser."), this.name = "NotSupportedError";
  }
};
var E = class extends i {
  constructor() {
    super("The passkey operation was cancelled."), this.name = "UserCancelledError";
  }
};
var S = class extends i {
  constructor() {
    super("This device is already registered as a passkey."), this.name = "PasskeyExistsError";
  }
};
var k = class extends i {
  constructor(e) {
    const s = e ?? "this domain";
    super(
      `Passkeys can't be used on ${s}. For local development, use localhost.`
    ), this.name = "InvalidDomainError";
  }
};
var c = (t) => {
  if (t instanceof i)
    return t;
  if (!(t instanceof Error))
    return new i("An unknown error occurred.");
  if (v(t))
    return new k(R());
  switch (t.name) {
    case "NotAllowedError":
      return new E();
    case "InvalidStateError":
      return new S();
    case "NotSupportedError":
      return new d();
    default:
      return new i(t.message);
  }
};
var v = (t) => A(t) === "ERROR_INVALID_DOMAIN";
var A = (t) => "code" in t && typeof t.code == "string" ? t.code : void 0;
var R = () => typeof globalThis.location?.hostname == "string" && globalThis.location.hostname.length > 0 ? globalThis.location.hostname : void 0;
var n = {};
var N = (t) => {
  n = {
    ...n,
    ...t,
    fetch: {
      ...n.fetch,
      ...t.fetch,
      headers: {
        ...n.fetch?.headers,
        ...t.fetch?.headers
      }
    }
  };
};
var O = () => typeof document > "u" ? null : T() || C();
var T = () => {
  const t = document.querySelector('meta[name="csrf-token"]');
  if (!t)
    return null;
  const e = t.getAttribute("content");
  return e ? { header: "X-CSRF-TOKEN", value: e } : null;
};
var C = () => {
  const t = "XSRF-TOKEN=", e = document.cookie.split("; ").find((o) => o.startsWith(t));
  if (!e)
    return null;
  const s = e.slice(t.length);
  return s ? { header: "X-XSRF-TOKEN", value: decodeURIComponent(s) } : null;
};
var u = async (t) => {
  const e = await fetch(t, {
    method: "GET",
    headers: {
      Accept: "application/json",
      ...n.fetch?.headers
    },
    credentials: n.fetch?.credentials ?? "same-origin"
  });
  return e.ok || await f(e), e.json();
};
var l = async (t, e) => {
  const s = O(), o = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...n.fetch?.headers
  };
  s && (o[s.header] = s.value);
  const r = await fetch(t, {
    method: "POST",
    headers: o,
    credentials: n.fetch?.credentials ?? "same-origin",
    body: JSON.stringify(e)
  });
  return r.ok || await f(r), r.json();
};
var f = async (t) => {
  let e = `Request failed with status ${t.status}`;
  try {
    const s = await t.json();
    s && typeof s == "object" && "message" in s && typeof s.message == "string" && (e = s.message);
  } catch {
  }
  throw new Error(e);
};
var a = {
  registerOptions: "/user/passkeys/options",
  registerStore: "/user/passkeys",
  verifyOptions: "/passkeys/login/options",
  verifySubmit: "/passkeys/login"
};
var P = {
  /**
   * Configure the passkeys client.
   */
  configure(t) {
    N(t);
  },
  /**
   * Check if the browser supports passkeys.
   */
  isSupported() {
    return browserSupportsWebAuthn();
  },
  /**
   * Check if the browser supports passkey autofill.
   */
  async isAutofillSupported() {
    return browserSupportsWebAuthnAutofill();
  },
  /**
   * Register a new passkey for the authenticated user.
   */
  async register(t) {
    if (!this.isSupported())
      throw new d();
    this.cancel();
    try {
      const e = p(t, {
        options: a.registerOptions,
        submit: a.registerStore
      }), { options: s } = await u(e.optionsRoute), o = await startRegistration({ optionsJSON: s }), r = {
        name: t.name,
        credential: o
      };
      return await l(
        e.submitRoute,
        r
      );
    } catch (e) {
      throw c(e);
    }
  },
  /**
   * Verify with a passkey.
   */
  async verify(t = {}) {
    if (!this.isSupported())
      throw new d();
    this.cancel();
    try {
      const e = p(t, {
        options: a.verifyOptions,
        submit: a.verifySubmit
      }), { options: s } = await u(
        e.optionsRoute
      ), r = { credential: await startAuthentication({ optionsJSON: s }) };
      return await l(e.submitRoute, r);
    } catch (e) {
      throw c(e);
    }
  },
  /**
   * Enable passkey autofill on the current page.
   *
   * Note that the page must have an input with `autocomplete="email webauthn"` to
   * anchor the browser's passkey picker dropdown.
   *
   * Returns the verification response on success, or `undefined` if autofill
   * is not supported or was cancelled.
   */
  async autofill(t = {}) {
    if (!(!this.isSupported() || !await this.isAutofillSupported()))
      try {
        const s = p(t, {
          options: a.verifyOptions,
          submit: a.verifySubmit
        }), { options: o } = await u(
          s.optionsRoute
        ), m = { credential: await startAuthentication({
          optionsJSON: o,
          useBrowserAutofill: true
        }) };
        return await l(s.submitRoute, m);
      } catch (s) {
        if (s instanceof Error && ["AbortError", "NotAllowedError"].includes(s.name))
          return;
        throw c(s);
      }
  },
  /**
   * Cancel any pending passkey operation.
   */
  cancel() {
    WebAuthnAbortService.cancelCeremony();
  }
};
var p = (t, e) => ({
  optionsRoute: t.routes?.options ?? e.options,
  submitRoute: t.routes?.submit ?? e.submit
});

// index.js
function canAttemptSignIn() {
  const deviceUuid = localStorage.getItem("device_uuid");
  if (!deviceUuid) {
    return false;
  }
  return localStorage.getItem("auth_method") === "password" || P.isSupported();
}
async function attemptSignIn() {
  if (localStorage.getItem("auth_method") === "password") {
    return { outcome: "fallback" };
  }
  if (!P.isSupported()) {
    return { outcome: "failure" };
  }
  P.configure({
    fetch: {
      headers: {
        "X-Device-Uuid": localStorage.getItem("device_uuid") ?? ""
      }
    }
  });
  try {
    const result = await P.verify();
    return { outcome: "success", redirect: result.redirect ?? "/" };
  } catch (error) {
    console.error(error);
    return { outcome: "failure" };
  }
}
function getDeviceCredentials() {
  return {
    device_uuid: localStorage.getItem("device_uuid"),
    auth_method: localStorage.getItem("auth_method")
  };
}
function clearDeviceCredentials() {
  localStorage.removeItem("device_uuid");
  localStorage.removeItem("auth_method");
}
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
}
async function postJson(url, body, { method = "POST", headers = {} } = {}) {
  let response;
  try {
    response = await fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrfToken(),
        ...headers
      },
      body: JSON.stringify(body)
    });
  } catch (error) {
    console.error(error);
    return { ok: false, code: "network_error" };
  }
  let data;
  try {
    data = await response.json();
  } catch (error) {
    console.error(error);
    return { ok: false, code: "network_error" };
  }
  if (!response.ok) {
    return {
      ok: false,
      code: data.errors ? "validation" : "server_error",
      errors: data.errors
    };
  }
  return { ok: true, data };
}
async function registerPasskey({ name, passkeyLabel }) {
  const trimmedName = (name ?? "").trim();
  if (!trimmedName) {
    return { outcome: "failure", code: "name_required" };
  }
  let registration;
  try {
    registration = await P.register({
      name: passkeyLabel,
      routes: {
        options: `/profile/passkey-options?name=${encodeURIComponent(trimmedName)}`,
        submit: "/profile"
      }
    });
  } catch (error) {
    console.error(error);
    return { outcome: "failure", code: "ceremony_failed" };
  }
  if (registration.device_uuid) {
    localStorage.setItem("device_uuid", registration.device_uuid);
  }
  const result = await postJson("/profile", { name: trimmedName }, { method: "PATCH" });
  if (!result.ok) {
    return { outcome: "failure", code: result.code, errors: result.errors };
  }
  return { outcome: "success", redirect: result.data.redirect ?? "/" };
}
async function registerWithPassword({ name, email, password, password_confirmation }) {
  const result = await postJson("/profile-password", { name, email, password, password_confirmation });
  if (!result.ok) {
    return { outcome: "failure", code: result.code, errors: result.errors };
  }
  if (result.data.device_uuid) {
    localStorage.setItem("device_uuid", result.data.device_uuid);
    localStorage.setItem("auth_method", "password");
  }
  return { outcome: "success", redirect: result.data.redirect ?? "/" };
}
async function signInWithPassword({ email, password }) {
  const result = await postJson(
    "/login",
    { email, password },
    { headers: { "X-Device-Uuid": localStorage.getItem("device_uuid") ?? "" } }
  );
  if (!result.ok) {
    return { outcome: "failure", code: result.code, errors: result.errors };
  }
  return { outcome: "success", redirect: result.data.redirect ?? "/" };
}
function readStrings() {
  const elements = document.querySelectorAll("[data-easy-auth-strings]");
  return Array.from(elements).reduce((strings, el) => {
    try {
      return Object.assign(strings, JSON.parse(el.textContent));
    } catch (error) {
      console.error(error);
      return strings;
    }
  }, {});
}
function joinErrors(errors) {
  return Object.values(errors ?? {}).flat().join(" ");
}
function initEasyAuth({ onStatus } = {}) {
  const strings = readStrings();
  const profileCreateForm = document.getElementById("profile-create-form");
  const showPasswordRegisterButton = document.getElementById("show-password-register");
  const passwordRegisterForm = document.getElementById("password-register-form");
  const signInForm = document.getElementById("sign-in-form");
  const nameInput = document.getElementById("name");
  const registerNameInput = document.getElementById("register-name");
  const passkeyStatus = document.getElementById("passkey-status");
  const passwordRegistrationStatus = document.getElementById("password-registration-status");
  const signInStatus = document.getElementById("sign-in-status");
  const deviceUuidEl = document.getElementById("device-uuid");
  const authMethodEl = document.getElementById("auth-method");
  const deviceResetStatus = document.getElementById("status");
  const clearDeviceButton = document.getElementById("clear");
  const alreadyRegisteredNotice = document.getElementById("already-registered-notice");
  const registrationForms = document.getElementById("registration-forms");
  const alreadyRegisteredReinviteButton = document.getElementById("already-registered-reinvite");
  function report(el, outcome, code, message) {
    if (onStatus) {
      onStatus({ outcome, code, message });
      return;
    }
    if (el) {
      el.textContent = message ?? "";
    }
  }
  function showPasswordRegisterForm() {
    profileCreateForm?.classList.add("hidden");
    showPasswordRegisterButton?.classList.add("hidden");
    passwordRegisterForm?.classList.remove("hidden");
    if (registerNameInput && !registerNameInput.value && nameInput?.value) {
      registerNameInput.value = nameInput.value;
    }
    if (passkeyStatus) {
      passkeyStatus.textContent = "";
    }
  }
  if (profileCreateForm && !P.isSupported()) {
    showPasswordRegisterForm();
  }
  if (alreadyRegisteredNotice && registrationForms) {
    const { device_uuid: deviceUuid, auth_method: authMethod } = getDeviceCredentials();
    if (deviceUuid) {
      alreadyRegisteredNotice.classList.remove("hidden");
      registrationForms.classList.add("hidden");
      alreadyRegisteredReinviteButton?.addEventListener("click", () => {
        if (authMethod === "password") {
          window.location.href = "/login";
          return;
        }
        alreadyRegisteredNotice.classList.add("hidden");
        registrationForms.classList.remove("hidden");
      });
    }
  }
  showPasswordRegisterButton?.addEventListener("click", () => showPasswordRegisterForm());
  profileCreateForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const name = new FormData(profileCreateForm).get("name");
    const result = await registerPasskey({ name, passkeyLabel: strings.passkeyLabel });
    if (result.outcome === "success") {
      report(passkeyStatus, "success", void 0, void 0);
      window.location.href = result.redirect;
      return;
    }
    const messages = {
      name_required: strings.nameRequired,
      ceremony_failed: strings.passkeyRegistrationFailed,
      validation: joinErrors(result.errors),
      server_error: strings.profileSaveFailed,
      network_error: strings.networkError
    };
    report(passkeyStatus, "failure", result.code, messages[result.code]);
    showPasswordRegisterButton?.classList.remove("hidden");
  });
  passwordRegisterForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(passwordRegisterForm));
    const result = await registerWithPassword(formData);
    if (result.outcome === "success") {
      report(passwordRegistrationStatus ?? passkeyStatus, "success", void 0, void 0);
      window.location.href = result.redirect;
      return;
    }
    const messages = {
      validation: joinErrors(result.errors),
      server_error: strings.passwordRegistrationFailed,
      network_error: strings.networkError
    };
    report(passwordRegistrationStatus ?? passkeyStatus, "failure", result.code, messages[result.code]);
  });
  signInForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(signInForm));
    const result = await signInWithPassword(formData);
    if (result.outcome === "success") {
      report(signInStatus, "success", void 0, void 0);
      window.location.href = result.redirect;
      return;
    }
    const messages = {
      validation: joinErrors(result.errors),
      server_error: strings.signInFailed,
      network_error: strings.networkError
    };
    report(signInStatus, "failure", result.code, messages[result.code]);
  });
  if (deviceUuidEl && authMethodEl) {
    const credentials = getDeviceCredentials();
    deviceUuidEl.textContent = credentials.device_uuid ?? strings.deviceNone;
    authMethodEl.textContent = credentials.auth_method ?? strings.deviceNone;
    clearDeviceButton?.addEventListener("click", () => {
      clearDeviceCredentials();
      deviceUuidEl.textContent = strings.deviceNone;
      authMethodEl.textContent = strings.deviceNone;
      if (deviceResetStatus) {
        deviceResetStatus.textContent = [strings.deviceCleared, strings.deviceNextStep].join(" ");
      }
    });
  }
  if (document.getElementById("account-deleted-page")) {
    clearDeviceCredentials();
  }
}
export {
  attemptSignIn,
  canAttemptSignIn,
  clearDeviceCredentials,
  getDeviceCredentials,
  initEasyAuth,
  registerPasskey,
  registerWithPassword,
  signInWithPassword
};
